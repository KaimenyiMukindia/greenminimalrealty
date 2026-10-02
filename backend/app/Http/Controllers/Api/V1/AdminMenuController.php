<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\MenuRequest;
use App\Http\Requests\Api\V1\Admin\ReorderRequest;
use App\Http\Resources\ContentResource;
use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMenuController extends Controller
{
    public function index(Request $request)
    {
        $query = Menu::query()->with('items');
        if ($request->exists('published')) $query->where('published', $request->boolean('published'));
        if ($request->filled('search')) $query->where('name', 'like', '%' . addcslashes((string) $request->query('search'), '%_\\') . '%');
        $direction = strtolower((string) $request->query('direction', in_array(strtolower((string) $request->query('order')), ['asc', 'desc'], true) ? $request->query('order') : 'asc'));
        return ContentResource::collection($query->orderBy('order', $direction === 'desc' ? 'desc' : 'asc')->paginate(min(max((int) $request->query('per_page', 20), 1), 100)));
    }

    public function show(int $id): ContentResource
    {
        return new ContentResource(Menu::query()->with('items')->findOrFail($id));
    }

    public function store(MenuRequest $request): ContentResource
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);
        $menu = DB::transaction(function () use ($data, $items): Menu {
            $menu = Menu::create($data);
            $this->syncItems($menu, $items);
            $this->syncPrimaryNavigation();
            return $menu;
        });

        return new ContentResource($menu->load('items'));
    }

    public function update(MenuRequest $request, int $id): ContentResource
    {
        $menu = Menu::findOrFail($id);
        $data = $request->validated();
        $items = $data['items'] ?? null;
        unset($data['items']);
        DB::transaction(function () use ($menu, $data, $items): void {
            $menu->update($data);
            if ($items !== null) $this->syncItems($menu, $items);
            $this->syncPrimaryNavigation();
        });

        return new ContentResource($menu->fresh()->load('items'));
    }

    public function destroy(int $id): array
    {
        abort_unless(request()->user()->can('delete-content'), 403);
        DB::transaction(function () use ($id): void {
            Menu::findOrFail($id)->delete();
            $this->syncPrimaryNavigation();
        });

        return ['message' => 'Deleted.'];
    }

    public function reorder(ReorderRequest $request): array
    {
        $ids = $request->validated('ids');
        $menuId = $request->validated('menu_id');
        DB::transaction(function () use ($ids, $menuId): void {
            if ($menuId !== null) {
                $menu = Menu::findOrFail($menuId);
                $found = $menu->menuItems()->whereKey($ids)->count();
                abort_unless($found === count($ids), 422, 'One or more items do not belong to this menu.');
                foreach ($ids as $order => $id) MenuItem::where('menu_id', $menu->id)->whereKey($id)->update(['order' => $order]);
            } else {
                $found = Menu::whereKey($ids)->count();
                abort_unless($found === count($ids), 422, 'One or more menus do not exist.');
                foreach ($ids as $order => $id) Menu::whereKey($id)->update(['order' => $order]);
            }
            AuditLog::create([
                'user_id' => request()->user()->id,
                'action' => 'content.reordered',
                'ip' => request()->ip() ?? '0.0.0.0',
                'user_agent' => request()->userAgent(),
                'meta' => ['resource' => 'menus', 'menu_id' => $menuId, 'ids' => $ids],
            ]);
            $this->syncPrimaryNavigation();
        });

        return ['message' => 'Reordered.'];
    }

    private function syncItems(Menu $menu, array $items): void
    {
        $keep = [];
        $createdIds = [];
        $clientKeys = array_column($items, 'client_key');
        foreach (array_values($items) as $position => $item) {
            if (! empty($item['parent_client_key'])) abort_unless(in_array($item['parent_client_key'], $clientKeys, true), 422, 'A menu item parent is missing.');
            $attributes = [
                'label' => $item['label'],
                'url' => $item['url'],
                'parent_id' => $item['parent_id'] ?? ($createdIds[$item['parent_client_key'] ?? ''] ?? null),
                'order' => $item['order'] ?? $position,
                'published' => $item['published'] ?? true,
            ];
            if (isset($item['id'])) {
                /** @var MenuItem|null $record */
                $record = $menu->menuItems()->whereKey($item['id'])->first();
                abort_unless($record !== null, 422, 'A menu item does not belong to this menu.');
                $record->update($attributes);
            } else {
                $record = $menu->menuItems()->create($attributes);
            }
            if (isset($item['client_key'])) $createdIds[$item['client_key']] = $record->getKey();
            $keep[] = $record->getKey();
        }
        foreach ($menu->menuItems()->whereKey($keep)->get() as $item) {
            if ($item->parent_id === null) continue;
            $parent = $menu->menuItems()->whereKey($item->parent_id)->first();
            abort_unless($parent !== null && $parent->parent_id === null && $parent->id !== $item->id && in_array($parent->id, $keep, true), 422, 'Menu item parents must be retained root items in the same menu.');
        }
        $menu->menuItems()->whereNotIn('id', $keep)->delete();
    }

    private function syncPrimaryNavigation(): void
    {
        $menu = Menu::query()->where('location', 'primary')->where('published', true)->first();
        $items = $menu
            ? $menu->items()->where('published', true)->get(['label', 'url'])->map(fn (MenuItem $item): array => ['label' => $item->label, 'to' => $item->url])->all()
            : [];
        SiteSetting::query()->firstOrCreate([])->update(['nav_items' => $items]);
    }
}

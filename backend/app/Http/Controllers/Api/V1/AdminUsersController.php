<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUsersController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->role === UserRole::Admin, 403);
        $query = User::query();
        if ($request->filled('role') && in_array($request->query('role'), array_column(UserRole::cases(), 'value'), true)) $query->where('role', $request->query('role'));
        if ($request->filled('search')) {
            $search = addcslashes(trim((string) $request->query('search')), '%_\\');
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        return UserResource::collection($query->orderBy('name', $direction)->paginate(min(max((int) $request->query('per_page', 20), 1), 100)));
    }

    public function update(UserUpdateRequest $request, int $id): UserResource
    {
        $user = User::findOrFail($id);
        $attributes = $request->validated();
        if ($user->role === UserRole::Admin && ($attributes['role'] ?? UserRole::Admin->value) !== UserRole::Admin->value) {
            abort_if(User::where('role', UserRole::Admin)->count() <= 1, 422, 'The last administrator cannot be demoted.');
        }
        $user->update($attributes);

        return new UserResource($user->fresh());
    }

    public function destroy(Request $request, int $id): array
    {
        abort_unless($request->user()?->role === UserRole::Admin, 403);
        $user = User::findOrFail($id);
        abort_if($user->is($request->user()), 422, 'You cannot remove your own account.');
        abort_if($user->role === UserRole::Admin && User::where('role', UserRole::Admin)->count() <= 1, 422, 'The last administrator cannot be removed.');
        $user->tokens()->delete();
        $user->delete();

        return ['message' => 'User removed.'];
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\MediaUploadRequest;
use App\Http\Resources\ContentResource;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::query();
        if ($request->filled('search')) {
            $search = addcslashes((string) $request->query('search'), '%_\\');
            $query->where(fn ($builder) => $builder->where('alt', 'like', "%{$search}%")->orWhere('path', 'like', "%{$search}%"));
        }
        $direction = strtolower((string) $request->query('direction', in_array(strtolower((string) $request->query('order')), ['asc', 'desc'], true) ? $request->query('order') : 'desc'));
        return ContentResource::collection($query->orderBy('created_at', $direction === 'asc' ? 'asc' : 'desc')->paginate(min(max((int) $request->query('per_page', 24), 1), 100)));
    }

    public function store(MediaUploadRequest $request): ContentResource
    {
        $file = $request->file('file');
        $path = $file->store('uploads/media', 'public');
        $media = Media::create(['path' => $path, 'url' => rtrim((string) config('app.url'), '/') . '/storage/' . $path, 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'alt' => $request->input('alt'), 'uploaded_by' => $request->user()->id]);
        return new ContentResource($media);
    }

    public function destroy(Media $media): array
    {
        abort_unless(request()->user()->can('delete-content'), 403);
        Storage::disk('public')->delete($media->path);
        $media->delete();
        return ['message' => 'Deleted.'];
    }
}
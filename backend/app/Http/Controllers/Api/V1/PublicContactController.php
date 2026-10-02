<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\StoreContactSubmissionRequest;
use App\Http\Resources\ContentResource;
use App\Models\ContactSubmission;

class PublicContactController extends Controller
{
    public function store(StoreContactSubmissionRequest $request): ContentResource
    {
        $submission = ContactSubmission::create($request->validated() + [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return new ContentResource($submission);
    }
}
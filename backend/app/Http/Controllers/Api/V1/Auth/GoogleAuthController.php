<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\GoogleExchangeRequest;
use App\Http\Resources\AuthResource;
use App\Services\AuthenticationService;

class GoogleAuthController extends Controller
{
    public function store(GoogleExchangeRequest $request, AuthenticationService $service): AuthResource
    {
        return new AuthResource($service->exchangeGoogleCode($request->validated('code'), $request->validated('verifier')));
    }
}

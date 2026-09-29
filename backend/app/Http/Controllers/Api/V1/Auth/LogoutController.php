<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RevokeAllTokens;
use App\Actions\Auth\RevokeCurrentToken;
use App\Http\Controllers\Controller;
use App\Http\Resources\StatusResource;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function destroy(Request $request, RevokeCurrentToken $action): StatusResource
    {
        $action->handle($request, $request->user());

        return new StatusResource(['message' => 'Signed out successfully.', 'code' => 'signed_out']);
    }

    public function destroyAll(Request $request, RevokeAllTokens $action): StatusResource
    {
        $action->handle($request, $request->user());

        return new StatusResource(['message' => 'All access tokens have been revoked.', 'code' => 'tokens_revoked']);
    }
}

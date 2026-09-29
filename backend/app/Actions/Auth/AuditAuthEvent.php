<?php

namespace App\Actions\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditAuthEvent
{
    /** @param array<string, scalar|null> $metadata */
    public function log(Request $request, string $event, ?User $user = null, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => $user?->getKey(),
            'action' => $event,
            'ip' => $request->ip() ?? '0.0.0.0',
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000) ?: null,
            'meta' => $metadata === [] ? null : $metadata,
        ]);
    }
}
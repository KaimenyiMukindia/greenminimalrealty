<?php

namespace App\Policies;

use App\Models\User;

class ContentPolicy
{
    public function viewAny(User $user): bool { return $user->role->value !== 'user'; }
    public function manageContent(User $user): bool { return in_array($user->role->value, ['admin', 'editor'], true); }
    public function deleteContent(User $user): bool { return $user->role->value === 'admin'; }
}
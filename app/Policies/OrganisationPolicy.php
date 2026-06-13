<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Organisation;
use App\Models\User;

class OrganisationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::ADMIN || $user->role === UserRole::AGENT;
    }

    public function view(User $user, Organisation $organisation): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function update(User $user, Organisation $organisation): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, Organisation $organisation): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}

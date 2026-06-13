<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return match ($user->role) {
            UserRole::ADMIN, UserRole::AGENT => true,
            UserRole::ORGANISATION_OWNER,
            UserRole::CLIENT => $user->organisation_id === $ticket->organisation_id,
        };
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return match ($user->role) {
            UserRole::ADMIN => true,
            UserRole::ORGANISATION_OWNER => $user->organisation_id === $ticket->organisation_id,
            UserRole::CLIENT => $user->id === $ticket->user_id,
            default => false,
        };
    }

    public function updateByAgent(User $user, Ticket $ticket): bool
    {
        return $user->role === UserRole::AGENT;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->role === UserRole::ADMIN || $user->id === $ticket->user_id;
    }
}

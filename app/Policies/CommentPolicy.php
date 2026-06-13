<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;

class CommentPolicy
{
    public function create(User $user, Ticket $ticket): bool
    {
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::AGENT) return true;

        return $user->organisation_id === $ticket->organisation_id;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->role === UserRole::ADMIN;
    }
}

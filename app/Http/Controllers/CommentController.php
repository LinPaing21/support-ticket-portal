<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validated();
        $isStaff = in_array($request->user()->role, [UserRole::ADMIN, UserRole::AGENT]);

        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal' => $isStaff && ($data['is_internal'] ?? false),
        ]);

        return to_route('tickets.show', $ticket);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): RedirectResponse
    {
        $comment->update($request->validated());

        return to_route('tickets.show', $comment->ticket_id);
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $ticketId = $comment->ticket_id;
        $comment->delete();

        return to_route('tickets.show', $ticketId);
    }
}

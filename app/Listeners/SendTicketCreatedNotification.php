<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\TicketCreated;
use App\Mail\TicketCreatedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendTicketCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket->load(['organisation', 'user']);

        User::where('role', UserRole::AGENT)->each(
            fn (User $agent) => Mail::to($agent)->queue(new TicketCreatedMail($ticket))
        );
    }
}

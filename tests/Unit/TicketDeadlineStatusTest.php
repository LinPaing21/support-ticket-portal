<?php

namespace Tests\Unit;

use App\Enums\DeadlineStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Tests\TestCase;

class TicketDeadlineStatusTest extends TestCase
{
    private function makeTicket(TicketStatus $status, string $slaDeadline): Ticket
    {
        $ticket = new Ticket();
        $ticket->forceFill([
            'status'       => $status,
            'priority'     => TicketPriority::MEDIUM,
            'sla_deadline' => $slaDeadline,
        ]);

        return $ticket;
    }

    public function test_resolved_ticket_is_completed_regardless_of_deadline(): void
    {
        $ticket = $this->makeTicket(TicketStatus::RESOLVED, now()->subDay()->toDateTimeString());

        $this->assertSame(DeadlineStatus::COMPLETED->value, $ticket->deadline_status);
    }

    public function test_closed_ticket_is_completed_regardless_of_deadline(): void
    {
        $ticket = $this->makeTicket(TicketStatus::CLOSED, now()->addDays(5)->toDateTimeString());

        $this->assertSame(DeadlineStatus::COMPLETED->value, $ticket->deadline_status);
    }

    public function test_open_ticket_past_deadline_is_overdue(): void
    {
        $ticket = $this->makeTicket(TicketStatus::OPEN, now()->subMinute()->toDateTimeString());

        $this->assertSame(DeadlineStatus::OVERDUE->value, $ticket->deadline_status);
    }

    public function test_in_progress_ticket_past_deadline_is_overdue(): void
    {
        $ticket = $this->makeTicket(TicketStatus::IN_PROGRESS, now()->subHours(2)->toDateTimeString());

        $this->assertSame(DeadlineStatus::OVERDUE->value, $ticket->deadline_status);
    }

    public function test_open_ticket_due_within_one_hour_is_due_soon(): void
    {
        $ticket = $this->makeTicket(TicketStatus::OPEN, now()->addMinutes(30)->toDateTimeString());

        $this->assertSame(DeadlineStatus::DUE_SOON->value, $ticket->deadline_status);
    }

    public function test_open_ticket_due_exactly_at_boundary_is_due_soon(): void
    {
        // 1 second under the 1-hour threshold → still due soon
        $ticket = $this->makeTicket(TicketStatus::OPEN, now()->addSeconds(3599)->toDateTimeString());

        $this->assertSame(DeadlineStatus::DUE_SOON->value, $ticket->deadline_status);
    }

    public function test_open_ticket_with_more_than_one_hour_remaining_is_on_track(): void
    {
        $ticket = $this->makeTicket(TicketStatus::OPEN, now()->addHours(2)->toDateTimeString());

        $this->assertSame(DeadlineStatus::ON_TRACK->value, $ticket->deadline_status);
    }

    public function test_in_progress_ticket_with_more_than_one_hour_remaining_is_on_track(): void
    {
        $ticket = $this->makeTicket(TicketStatus::IN_PROGRESS, now()->addDays(1)->toDateTimeString());

        $this->assertSame(DeadlineStatus::ON_TRACK->value, $ticket->deadline_status);
    }
}

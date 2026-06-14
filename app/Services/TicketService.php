<?php

namespace App\Services;

use App\DTOs\TicketFilterDTO;
use App\Enums\DeadlineStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class TicketService
{
    public function __construct(
        private readonly TicketRepository $repository,
    ) {}

    /** @return array<int, string> */
    public function sortable(): array
    {
        return $this->repository->sortable();
    }

    /** @return array<int, array{value: string, label: string}> */
    public function statusOptions(): array
    {
        return collect(TicketStatus::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => ucwords(str_replace('_', ' ', $s->value)),
        ])->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    public function priorityOptions(): array
    {
        return collect(TicketPriority::cases())->map(fn ($p) => [
            'value' => $p->value,
            'label' => ucwords($p->value),
        ])->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    public function deadlineStatusOptions(): array
    {
        return collect(DeadlineStatus::cases())->map(fn ($s) => [
            'value' => $s->value,
            'label' => ucwords(str_replace('-', ' ', $s->value)),
        ])->all();
    }

    public function list(User $user, TicketFilterDTO $filters): LengthAwarePaginator
    {
        $organisationId = match ($user->role) {
            UserRole::ORGANISATION_OWNER, UserRole::CLIENT => $user->organisation_id,
            default => $filters->organisationId,
        };

        return $this->repository->paginate($filters->withOrganisationId($organisationId));
    }

    public function create(User $user, array $data): Ticket
    {
        $data['user_id'] = $user->id;
        $data['organisation_id'] = $user->isStaff
            ? $data['organisation_id']
            : $user->organisation_id;
        $data['sla_deadline'] = $this->calculateSlaDeadline(TicketPriority::from($data['priority']));

        $ticket = $this->repository->create($data);

        if (!$user->isStaff) {
            TicketCreated::dispatch($ticket);
        }

        return $ticket;
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        if (isset($data['status'])) {
            $newStatus = $data['status'];
            $oldStatus = $ticket->status->value;

            if ($newStatus !== $oldStatus) {
                Log::info(auth()->user()->name . "  changed status from {$oldStatus} to {$newStatus}", [
                    'ticket_id' => $ticket->id,
                ]);
            }
        }

        if (isset($data['priority'])) {
            $newPriority = TicketPriority::from($data['priority']);

            if ($newPriority !== $ticket->priority) {
                Log::info(auth()->user()->name . " changed priority from {$ticket->priority->value} to {$newPriority->value}", [
                    'ticket_id' => $ticket->id,
                ]);

                $data['sla_deadline'] = $this->calculateSlaDeadline($newPriority);
            }
        }

        return $this->repository->update($ticket, $data);
    }

    public function delete(Ticket $ticket): void
    {
        $this->repository->delete($ticket);
    }

    private function calculateSlaDeadline(TicketPriority $priority): CarbonInterface
    {
        return match ($priority) {
            TicketPriority::URGENT => now()->addHour(),
            TicketPriority::HIGH => now()->addHours(4),
            TicketPriority::MEDIUM => now()->addHours(12),
            TicketPriority::LOW => now()->addDays(2),
        };
    }
}

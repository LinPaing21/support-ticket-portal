<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\TicketRepository;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function list(User $user, string $sort, string $direction, string $search = ''): LengthAwarePaginator
    {
        $organisationId = match ($user->role) {
            UserRole::ORGANISATION_OWNER, UserRole::CLIENT => $user->organisation_id,
            default => null,
        };

        return $this->repository->paginate($sort, $direction, $search, $organisationId);
    }

    public function create(User $user, array $data): Ticket
    {
        $data['user_id'] = $user->id;
        $data['organisation_id'] = $user->role === UserRole::ADMIN
            ? $data['organisation_id']
            : $user->organisation_id;
        $data['sla_deadline'] = $this->calculateSlaDeadline(TicketPriority::from($data['priority']));

        return $this->repository->create($data);
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        if (isset($data['priority'])) {
            $newPriority = TicketPriority::from($data['priority']);

            if ($newPriority !== $ticket->priority) {
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

<?php

namespace App\Repositories;

use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketRepository
{
    /** @return array<int, string> */
    public function sortable(): array
    {
        return (new Ticket)->sortable;
    }

    public function paginate(
        string $sort,
        string $direction,
        string $search = '',
        ?int $organisationId = null,
        ?int $userId = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return Ticket::with(['organisation', 'user', 'assignedAgent'])
            ->globalSearch($search)
            ->sorting($sort, $direction)
            ->when($organisationId, fn ($q) => $q->where('organisation_id', $organisationId))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Ticket
    {
        return Ticket::create($data);
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);

        return $ticket;
    }

    public function delete(Ticket $ticket): void
    {
        $ticket->delete();
    }
}

<?php

namespace App\Repositories;

use App\DTOs\TicketFilterDTO;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketRepository
{
    /** @return array<int, string> */
    public function sortable(): array
    {
        return (new Ticket)->sortable;
    }

    public function paginate(TicketFilterDTO $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Ticket::with(['organisation', 'user', 'assignedAgent'])
            ->globalSearch($filters->search)
            ->sorting($filters->sort, $filters->direction)
            ->when($filters->organisationId, fn ($q) => $q->where('organisation_id', $filters->organisationId))
            ->deadlineStatus($filters->deadlineStatus)
            ->when($filters->status, fn ($q) => $q->where('status', $filters->status))
            ->when($filters->priority, fn ($q) => $q->where('priority', $filters->priority))
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

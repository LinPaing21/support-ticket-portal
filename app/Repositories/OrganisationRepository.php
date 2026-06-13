<?php

namespace App\Repositories;

use App\Models\Organisation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrganisationRepository
{
    /** @return array<int, string> */
    public function sortable(): array
    {
        return (new Organisation)->sortable;
    }

    public function paginate(string $sort, string $direction, string $search = '', int $perPage = 15): LengthAwarePaginator
    {
        return Organisation::globalSearch($search)
            ->sorting($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Organisation
    {
        return Organisation::create($data);
    }

    public function update(Organisation $organisation, array $data): Organisation
    {
        $organisation->update($data);

        return $organisation;
    }

    public function delete(Organisation $organisation): void
    {
        $organisation->delete();
    }
}

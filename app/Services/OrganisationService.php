<?php

namespace App\Services;

use App\Models\Organisation;
use App\Repositories\OrganisationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class OrganisationService
{
    public function __construct(
        private readonly OrganisationRepository $repository,
    ) {}

    /** @return array<int, string> */
    public function sortable(): array
    {
        return $this->repository->sortable();
    }

    public function list(string $sort, string $direction, string $search = ''): LengthAwarePaginator
    {
        return $this->repository->paginate($sort, $direction, $search);
    }

    public function create(array $data): Organisation
    {
        $data['short_code'] = $this->generateShortCode();

        return $this->repository->create($data);
    }

    public function update(Organisation $organisation, array $data): Organisation
    {
        return $this->repository->update($organisation, $data);
    }

    public function delete(Organisation $organisation): void
    {
        $this->repository->delete($organisation);
    }

    private function generateShortCode(): string
    {
        do {
            $code = Str::upper(Str::random(7));
        } while (Organisation::where('short_code', $code)->exists());

        return $code;
    }
}

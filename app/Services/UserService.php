<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        private readonly UserRepository $repository,
    ) {}

    /** @return array<int, string> */
    public function sortable(): array
    {
        return $this->repository->sortable();
    }

    public function list(string $sort, string $direction, string $search = '', ?string $role = null, ?int $organisationId = null): LengthAwarePaginator
    {
        return $this->repository->paginate($sort, $direction, $search, $role, $organisationId);
    }

    /** @return array<int, array{value: string, label: string}> */
    public function roleOptions(): array
    {
        return collect(UserRole::cases())->map(fn ($r) => [
            'value' => $r->value,
            'label' => ucwords(str_replace('_', ' ', $r->value)),
        ])->all();
    }

    public function create(array $data): User
    {
        return $this->repository->create($data);
    }

    public function update(User $user, array $data): User
    {
        return $this->repository->update($user, $data);
    }

    public function delete(User $user): void
    {
        $this->repository->delete($user);
    }
}

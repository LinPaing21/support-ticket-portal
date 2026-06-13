<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    /** @return array<int, string> */
    public function sortable(): array
    {
        return (new User)->sortable;
    }

    public function paginate(string $sort, string $direction, int $perPage = 15): LengthAwarePaginator
    {
        return User::with('organisation')
            ->sorting($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}

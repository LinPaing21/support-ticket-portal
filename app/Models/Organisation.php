<?php

namespace App\Models;

use App\Traits\HasTableFilters;
use Database\Factories\OrganisationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $short_code
 * @property Carbon $joined_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'short_code', 'joined_at'])]
class Organisation extends Model
{
    /** @use HasFactory<OrganisationFactory> */
    use HasFactory, HasTableFilters;

    /** @var array<int, string> */
    public array $filterable = ['name', 'short_code'];

    /** @var array<int, string> */
    public array $sortable = ['name', 'short_code', 'joined_at', 'created_at'];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}

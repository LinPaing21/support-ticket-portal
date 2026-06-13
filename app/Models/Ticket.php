<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Traits\HasTableFilters;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organisation_id
 * @property int $user_id
 * @property int|null $assigned_agent_id
 * @property string $title
 * @property string $description
 * @property TicketStatus $status
 * @property TicketPriority $priority
 * @property Carbon $sla_deadline
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $deadline_status
 */
#[Fillable(['organisation_id', 'user_id', 'assigned_agent_id', 'title', 'description', 'status', 'priority', 'sla_deadline'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, HasTableFilters;

    /** @var array<int, string> */
    protected $appends = ['deadline_status'];

    /** @var array<int, string> */
    public array $filterable = ['title', 'description'];

    /** @var array<int, string> */
    public array $sortable = ['title', 'status', 'priority', 'sla_deadline', 'created_at'];

    protected function deadlineStatus(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $secondsRemaining = now()->diffInSeconds($this->sla_deadline, false);

                if ($secondsRemaining <= 0) { // pass deadline
                    return 'overdue';
                }

                if ($secondsRemaining <= 3600) { // in 1 hour
                    return 'due-soon';
                }

                return 'on-track';
            }
        );
    }

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'sla_deadline' => 'datetime',
        ];
    }

    /** @return BelongsTo<Organisation, $this> */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}

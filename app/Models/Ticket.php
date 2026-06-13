<?php

namespace App\Models;

use App\Enums\DeadlineStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Traits\HasTableFilters;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    protected $appends = ['deadline_status', 'sla_deadline_formatted', 'created_at_formatted'];

    /** @var array<int, string> */
    public array $filterable = ['title', 'description'];

    /** @var array<int, string> */
    public array $sortable = ['title', 'status', 'priority', 'sla_deadline', 'created_at'];

    protected function deadlineStatus(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->status === TicketStatus::RESOLVED || $this->status === TicketStatus::CLOSED) {
                    return DeadlineStatus::COMPLETED->value;
                }

                $secondsRemaining = now()->diffInSeconds($this->sla_deadline, false);

                if ($secondsRemaining <= 0) {
                    return DeadlineStatus::OVERDUE->value;
                }

                if ($secondsRemaining <= 3600) {
                    return DeadlineStatus::DUE_SOON->value;
                }

                return DeadlineStatus::ON_TRACK->value;
            }
        );
    }

    protected function slaDeadlineFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sla_deadline?->format('M j, Y, g:i A').' UTC'
        );
    }

    protected function createdAtFormatted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at?->format('M j, Y')
        );
    }

    /**
     * Filters by deadline status using SQL conditions that mirror the deadlineStatus accessor logic.
     * Each case translates to the equivalent time-based or status-based database condition so the
     * filter runs at the query level rather than post-loading all rows and discarding non-matches.
     *
     * - completed : ticket is resolved or closed (SLA no longer relevant)
     * - overdue   : open/in-progress and past the SLA deadline
     * - due-soon  : open/in-progress and deadline within the next hour
     * - on-track  : open/in-progress and more than one hour remaining
     */
    public function scopeDeadlineStatus(Builder $query, ?DeadlineStatus $status): Builder
    {
        if ($status === null) {
            return $query;
        }

        $closed = [TicketStatus::RESOLVED->value, TicketStatus::CLOSED->value];

        return match ($status) {
            DeadlineStatus::COMPLETED => $query->whereIn('status', $closed),
            DeadlineStatus::OVERDUE => $query->whereNotIn('status', $closed)->where('sla_deadline', '<=', now()),
            DeadlineStatus::DUE_SOON => $query->whereNotIn('status', $closed)->where('sla_deadline', '>', now())->where('sla_deadline', '<=', now()->addHour()),
            DeadlineStatus::ON_TRACK => $query->whereNotIn('status', $closed)->where('sla_deadline', '>', now()->addHour()),
        };
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

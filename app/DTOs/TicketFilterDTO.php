<?php

namespace App\DTOs;

use App\Enums\DeadlineStatus;
use Illuminate\Http\Request;

readonly class TicketFilterDTO
{
    public function __construct(
        public readonly string $sort = 'created_at',
        public readonly string $direction = 'desc',
        public readonly string $search = '',
        public readonly ?int $organisationId = null,
        public readonly ?string $status = null,
        public readonly ?string $priority = null,
        public readonly ?DeadlineStatus $deadlineStatus = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            sort: $request->string('sort', 'created_at'),
            direction: $request->string('direction', 'desc'),
            search: $request->string('search', ''),
            organisationId: $request->integer('organisation_id') ?: null,
            status: $request->string('status') ?: null,
            priority: $request->string('priority') ?: null,
            deadlineStatus: DeadlineStatus::tryFrom($request->string('deadline_status', '')),
        );
    }

    public function withOrganisationId(?int $organisationId): self
    {
        return new self(
            sort: $this->sort,
            direction: $this->direction,
            search: $this->search,
            organisationId: $organisationId,
            status: $this->status,
            priority: $this->priority,
            deadlineStatus: $this->deadlineStatus,
        );
    }
}

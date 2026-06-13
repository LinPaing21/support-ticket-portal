<?php

namespace App\Http\Controllers;

use App\DTOs\TicketFilterDTO;
use App\Enums\UserRole;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Comment;
use App\Models\Organisation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\OrganisationService;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $service,
        private readonly OrganisationService $organisationService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Ticket::class);

        $user = $request->user();
        $filters = TicketFilterDTO::fromRequest($request);

        return Inertia::render('tickets/Index', [
            'tickets' => $this->service->list($user, $filters),
            'sortable' => $this->service->sortable(),
            'statusOptions' => $this->service->statusOptions(),
            'priorityOptions' => $this->service->priorityOptions(),
            'deadlineStatusOptions' => $this->service->deadlineStatusOptions(),
            'organisations' => $user->isStaff
                ? $this->organisationService->getOrganisationsForSelect()
                : [],
            'filters' => [
                'sort' => $filters->sort,
                'direction' => $filters->direction,
                'search' => $filters->search,
                'status' => $filters->status ?? '',
                'priority' => $filters->priority ?? '',
                'deadline_status' => $filters->deadlineStatus?->value ?? '',
                'organisation_id' => $filters->organisationId ?? '',
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Ticket::class);

        $user = $request->user();

        return Inertia::render('tickets/Create', [
            'organisations' => $user->can('viewAny', Organisation::class)
                ? $this->organisationService->getOrganisationsForSelect()
                : [],
            'agents' => $user->can('viewAnyAgent', User::class)
                ? User::where('role', UserRole::AGENT->value)->select('id', 'name')->orderBy('name')->get()
                : [],
            'priorities' => $this->service->priorityOptions(),
            'isAdmin' => $user->role === UserRole::ADMIN,
            'isAgent' => $user->role === UserRole::AGENT,
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->service->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ticket created.']);

        return to_route('tickets.show', ['ticket' => $ticket]);
    }

    public function show(Request $request, Ticket $ticket): Response
    {
        Gate::authorize('view', $ticket);

        $user = $request->user();

        $comments = $ticket->comments()
            ->with('user:id,name,role')
            ->when(! $user->isStaff, fn ($q) => $q->where('is_internal', false))
            ->oldest()
            ->paginate(10)
            ->through(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'is_internal' => $comment->is_internal,
                'created_at' => $comment->created_at,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'role' => $comment->user->role->value,
                ],
                'can' => [
                    'update' => $user->can('update', $comment),
                    'delete' => $user->can('delete', $comment),
                ],
            ]);

        return Inertia::render('tickets/Show', [
            'ticket' => $ticket->load(['organisation', 'user', 'assignedAgent']),
            'comments' => $comments,
            'ticketUserId' => $ticket->user_id,
            'isStaff' => $user->isStaff,
            'can' => [
                'edit' => $user->canAny(['update', 'updateByAgent'], $ticket),
                'delete' => $user->can('delete', $ticket),
                'comment' => $user->can('create', [Comment::class, $ticket]),
            ],
        ]);
    }

    public function edit(Request $request, Ticket $ticket): Response
    {
        Gate::any(['update', 'updateByAgent'], $ticket);

        $user = $request->user();

        return Inertia::render('tickets/Edit', [
            'ticket' => $ticket,
            'organisations' => $user->can('viewAny', Organisation::class)
                ? $this->organisationService->getOrganisationsForSelect()
                : [],
            'agents' => $user->can('viewAnyAgent', User::class)
                ? User::where('role', UserRole::AGENT->value)->select('id', 'name')->orderBy('name')->get()
                : [],
            'priorities' => $this->service->priorityOptions(),
            'statuses' => $this->service->statusOptions(),
            'isAdmin' => $user->role === UserRole::ADMIN,
            'isAgent' => $user->role === UserRole::AGENT,
            'isAgentUpdate' => $user->can('updateByAgent', $ticket),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->service->update($ticket, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ticket updated.']);

        return to_route('tickets.show', ['ticket' => $ticket]);
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        Gate::authorize('delete', $ticket);

        $this->service->delete($ticket);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ticket deleted.']);

        return to_route('tickets.index');
    }
}

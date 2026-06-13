<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Comment;
use App\Models\Organisation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(private readonly TicketService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Ticket::class);

        $user = $request->user();
        $sort = $request->string('sort', 'created_at');
        $direction = $request->string('direction', 'desc');
        $search = $request->string('search', '');

        return Inertia::render('tickets/Index', [
            'tickets' => $this->service->list($user, $sort, $direction, $search),
            'sortable' => $this->service->sortable(),
            'filters' => [
                'sort' => $sort,
                'direction' => $direction,
                'search' => $search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Ticket::class);

        $user = $request->user();

        return Inertia::render('tickets/Create', [
            'organisations' => $user->can('viewAny', Organisation::class)
                ? Organisation::select('id', 'name')->orderBy('name')->get()
                : [],
            'agents' => $user->can('viewAnyAgent', User::class) ? User::where('role', UserRole::AGENT->value)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                : [],
            'priorities' => collect(TicketPriority::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => ucwords(str_replace('_', ' ', $p->value)),
            ]),
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
        $isStaff = \in_array($user->role, [UserRole::ADMIN, UserRole::AGENT]);

        $comments = $ticket->comments()
            ->with('user:id,name,role')
            ->when(! $isStaff, fn ($q) => $q->where('is_internal', false))
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
            'isStaff' => $isStaff,
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
                ? Organisation::select('id', 'name')->orderBy('name')->get()
                : [],
            'agents' => $user->can('viewAnyAgent', User::class) ? User::where('role', UserRole::AGENT->value)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                : [],
            'priorities' => collect(TicketPriority::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => ucwords(str_replace('_', ' ', $p->value)),
            ]),
            'statuses' => collect(TicketStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => ucwords(str_replace('_', ' ', $s->value)),
            ]),
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

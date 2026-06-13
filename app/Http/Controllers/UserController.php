<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Organisation;
use App\Models\User;
use App\Services\OrganisationService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $service,
        private readonly OrganisationService $organisationService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $sort = $request->string('sort', 'created_at');
        $direction = $request->string('direction', 'desc');
        $search = $request->string('search', '');
        $role = $request->string('role') ?: null;
        $organisationId = $request->integer('organisation_id') ?: null;

        return Inertia::render('users/Index', [
            'users' => $this->service->list($sort, $direction, $search, $role, $organisationId),
            'sortable' => $this->service->sortable(),
            'roleOptions' => $this->service->roleOptions(),
            'organisations' => $this->organisationService->getOrganisationsForSelect(),
            'filters' => [
                'sort' => $sort,
                'direction' => $direction,
                'search' => $search,
                'role' => $role ?? '',
                'organisation_id' => $organisationId ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('users/Create', [
            'organisations' => Organisation::select('id', 'name')->orderBy('name')->get(),
            'roles' => collect(UserRole::cases())->map(fn ($role) => ['value' => $role->value, 'label' => ucwords(str_replace('_', ' ', $role->value))]),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User created.']);

        return to_route('users.index');
    }

    public function show(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        return Inertia::render('users/Show', [
            'user' => $user->load('organisation'),
            'can' => [
                'edit' => $request->user()->can('update', $user),
                'delete' => $request->user()->can('delete', $user),
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('users/Edit', [
            'user' => $user,
            'organisations' => Organisation::select('id', 'name')->orderBy('name')->get(),
            'roles' => collect(UserRole::cases())->map(fn ($role) => ['value' => $role->value, 'label' => ucwords(str_replace('_', ' ', $role->value))]),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User updated.']);

        return to_route('users.show', ['user' => $user]);
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $this->service->delete($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User deleted.']);

        return to_route('users.index');
    }
}

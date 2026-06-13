<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganisationRequest;
use App\Http\Requests\UpdateOrganisationRequest;
use App\Models\Organisation;
use App\Services\OrganisationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganisationController extends Controller
{
    public function __construct(private readonly OrganisationService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Organisation::class);

        $sort = $request->string('sort', 'created_at');
        $direction = $request->string('direction', 'desc');
        $search = $request->string('search', '');

        return Inertia::render('organisations/Index', [
            'organisations' => $this->service->list($sort, $direction, $search),
            'sortable' => $this->service->sortable(),
            'filters' => [
                'sort' => $sort,
                'direction' => $direction,
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Organisation::class);

        return Inertia::render('organisations/Create');
    }

    public function store(StoreOrganisationRequest $request): RedirectResponse
    {
        $organisation = $this->service->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organisation created.']);

        return to_route('organisations.show', ['organisation' => $organisation]);
    }

    public function show(Request $request, Organisation $organisation): Response
    {
        Gate::authorize('view', $organisation);

        return Inertia::render('organisations/Show', [
            'organisation' => $organisation->load('users'),
            'can' => [
                'edit' => $request->user()->can('update', $organisation),
                'delete' => $request->user()->can('delete', $organisation),
            ],
        ]);
    }

    public function edit(Organisation $organisation): Response
    {
        Gate::authorize('update', $organisation);

        return Inertia::render('organisations/Edit', [
            'organisation' => $organisation,
        ]);
    }

    public function update(UpdateOrganisationRequest $request, Organisation $organisation): RedirectResponse
    {
        $this->service->update($organisation, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organisation updated.']);

        return to_route('organisations.show', ['organisation' => $organisation]);
    }

    public function destroy(Organisation $organisation): RedirectResponse
    {
        Gate::authorize('delete', $organisation);

        $this->service->delete($organisation);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Organisation deleted.']);

        return to_route('organisations.index');
    }
}

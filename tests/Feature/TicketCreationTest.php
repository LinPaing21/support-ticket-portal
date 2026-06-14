<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Events\TicketCreated;
use App\Models\Organisation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketCreationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'       => 'Test ticket',
            'description' => 'A description',
            'priority'    => TicketPriority::MEDIUM->value,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Guest
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('tickets.store'), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Admin
    // -------------------------------------------------------------------------

    public function test_admin_can_create_ticket_for_any_organisation(): void
    {
        $admin = User::factory()->admin()->create();
        $org   = Organisation::factory()->create();
        $agent = User::factory()->agent()->create();

        $this->actingAs($admin)
            ->post(route('tickets.store'), $this->validPayload([
                'organisation_id'  => $org->id,
                'assigned_agent_id' => $agent->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'organisation_id'   => $org->id,
            'assigned_agent_id' => $agent->id,
            'user_id'           => $admin->id,
            'priority'          => TicketPriority::MEDIUM->value,
        ]);
    }

    public function test_admin_ticket_requires_organisation_id(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('tickets.store'), $this->validPayload())
            ->assertSessionHasErrors('organisation_id');
    }

    public function test_admin_ticket_sla_deadline_is_set_based_on_priority(): void
    {
        $admin = User::factory()->admin()->create();
        $org   = Organisation::factory()->create();

        $this->actingAs($admin)
            ->post(route('tickets.store'), $this->validPayload([
                'organisation_id' => $org->id,
                'priority'        => TicketPriority::URGENT->value,
            ]));

        $ticket = Ticket::latest()->first();

        // Urgent SLA = 1 hour; allow a 10-second window for test execution time
        $this->assertEqualsWithDelta(now()->addHour()->timestamp, $ticket->sla_deadline->timestamp, 10);
    }

    public function test_admin_creating_ticket_does_not_dispatch_ticket_created_event(): void
    {
        Event::fake();

        $admin = User::factory()->admin()->create();
        $org   = Organisation::factory()->create();

        $this->actingAs($admin)
            ->post(route('tickets.store'), $this->validPayload([
                'organisation_id' => $org->id,
            ]));

        Event::assertNotDispatched(TicketCreated::class);
    }

    // -------------------------------------------------------------------------
    // Agent
    // -------------------------------------------------------------------------

    public function test_agent_can_create_ticket_for_any_organisation(): void
    {
        $agent = User::factory()->agent()->create();
        $org   = Organisation::factory()->create();

        $this->actingAs($agent)
            ->post(route('tickets.store'), $this->validPayload([
                'organisation_id' => $org->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'organisation_id' => $org->id,
            'user_id'         => $agent->id,
        ]);
    }

    public function test_agent_ticket_requires_organisation_id(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)
            ->post(route('tickets.store'), $this->validPayload())
            ->assertSessionHasErrors('organisation_id');
    }

    public function test_agent_creating_ticket_does_not_dispatch_ticket_created_event(): void
    {
        Event::fake();

        $agent = User::factory()->agent()->create();
        $org   = Organisation::factory()->create();

        $this->actingAs($agent)
            ->post(route('tickets.store'), $this->validPayload([
                'organisation_id' => $org->id,
            ]));

        Event::assertNotDispatched(TicketCreated::class);
    }

    // -------------------------------------------------------------------------
    // Organisation Owner
    // -------------------------------------------------------------------------

    public function test_organisation_owner_can_create_ticket_for_their_organisation(): void
    {
        $org   = Organisation::factory()->create();
        $owner = User::factory()->organisationOwner()->create(['organisation_id' => $org->id]);

        $this->actingAs($owner)
            ->post(route('tickets.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'organisation_id' => $org->id,
            'user_id'         => $owner->id,
        ]);
    }

    public function test_organisation_owner_ticket_organisation_is_scoped_to_their_own(): void
    {
        $org      = Organisation::factory()->create();
        $otherOrg = Organisation::factory()->create();
        $owner    = User::factory()->organisationOwner()->create(['organisation_id' => $org->id]);

        // Even if organisation_id is sent, it must be ignored and their own used
        $this->actingAs($owner)
            ->post(route('tickets.store'), $this->validPayload([
                'organisation_id' => $otherOrg->id,
            ]));

        $this->assertDatabaseHas('tickets', ['organisation_id' => $org->id]);
        $this->assertDatabaseMissing('tickets', ['organisation_id' => $otherOrg->id]);
    }

    public function test_organisation_owner_creating_ticket_dispatches_ticket_created_event(): void
    {
        Event::fake();

        $org   = Organisation::factory()->create();
        $owner = User::factory()->organisationOwner()->create(['organisation_id' => $org->id]);

        $this->actingAs($owner)
            ->post(route('tickets.store'), $this->validPayload());

        Event::assertDispatched(TicketCreated::class);
    }

    // -------------------------------------------------------------------------
    // Client
    // -------------------------------------------------------------------------

    public function test_client_can_create_ticket_for_their_organisation(): void
    {
        $org    = Organisation::factory()->create();
        $client = User::factory()->create([
            'role'            => UserRole::CLIENT,
            'organisation_id' => $org->id,
        ]);

        $this->actingAs($client)
            ->post(route('tickets.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'organisation_id' => $org->id,
            'user_id'         => $client->id,
        ]);
    }

    public function test_client_ticket_organisation_is_scoped_to_their_own(): void
    {
        $org      = Organisation::factory()->create();
        $otherOrg = Organisation::factory()->create();
        $client   = User::factory()->create([
            'role'            => UserRole::CLIENT,
            'organisation_id' => $org->id,
        ]);

        $this->actingAs($client)
            ->post(route('tickets.store'), $this->validPayload([
                'organisation_id' => $otherOrg->id,
            ]));

        $this->assertDatabaseHas('tickets', ['organisation_id' => $org->id]);
        $this->assertDatabaseMissing('tickets', ['organisation_id' => $otherOrg->id]);
    }

    public function test_client_creating_ticket_dispatches_ticket_created_event(): void
    {
        Event::fake();

        $org    = Organisation::factory()->create();
        $client = User::factory()->create([
            'role'            => UserRole::CLIENT,
            'organisation_id' => $org->id,
        ]);

        $this->actingAs($client)
            ->post(route('tickets.store'), $this->validPayload());

        Event::assertDispatched(TicketCreated::class);
    }

    // -------------------------------------------------------------------------
    // Validation (shared)
    // -------------------------------------------------------------------------

    public function test_title_is_required(): void
    {
        $org    = Organisation::factory()->create();
        $client = User::factory()->create([
            'role'            => UserRole::CLIENT,
            'organisation_id' => $org->id,
        ]);

        $this->actingAs($client)
            ->post(route('tickets.store'), $this->validPayload(['title' => '']))
            ->assertSessionHasErrors('title');
    }

    public function test_description_is_required(): void
    {
        $org    = Organisation::factory()->create();
        $client = User::factory()->create([
            'role'            => UserRole::CLIENT,
            'organisation_id' => $org->id,
        ]);

        $this->actingAs($client)
            ->post(route('tickets.store'), $this->validPayload(['description' => '']))
            ->assertSessionHasErrors('description');
    }

    public function test_priority_must_be_a_valid_enum_value(): void
    {
        $org    = Organisation::factory()->create();
        $client = User::factory()->create([
            'role'            => UserRole::CLIENT,
            'organisation_id' => $org->id,
        ]);

        $this->actingAs($client)
            ->post(route('tickets.store'), $this->validPayload(['priority' => 'invalid']))
            ->assertSessionHasErrors('priority');
    }

    // -------------------------------------------------------------------------
    // Create page — field visibility by role
    // -------------------------------------------------------------------------

    public function test_admin_sees_organisation_and_agent_fields(): void
    {
        $admin = User::factory()->admin()->create();
        Organisation::factory()->count(2)->create();
        User::factory()->agent()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('tickets.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tickets/Create')
                ->where('isAdmin', true)
                ->where('isAgent', false)
                ->has('organisations', 2)
                ->has('agents', 2)
            );
    }

    public function test_agent_sees_organisation_and_agent_fields(): void
    {
        $agent = User::factory()->agent()->create();
        Organisation::factory()->count(3)->create();
        User::factory()->agent()->count(2)->create();

        // agents list includes the acting agent + 2 others = 3 total
        $this->actingAs($agent)
            ->get(route('tickets.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tickets/Create')
                ->where('isAdmin', false)
                ->where('isAgent', true)
                ->has('organisations', 3)
                ->has('agents', 3)
            );
    }

    public function test_organisation_owner_does_not_see_organisation_or_agent_fields(): void
    {
        $org   = Organisation::factory()->create();
        $owner = User::factory()->organisationOwner()->create(['organisation_id' => $org->id]);

        $this->actingAs($owner)
            ->get(route('tickets.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tickets/Create')
                ->where('isAdmin', false)
                ->where('isAgent', false)
                ->has('organisations', 0)
                ->has('agents', 0)
            );
    }

    public function test_client_does_not_see_organisation_or_agent_fields(): void
    {
        $org    = Organisation::factory()->create();
        $client = User::factory()->create([
            'role'            => UserRole::CLIENT,
            'organisation_id' => $org->id,
        ]);

        $this->actingAs($client)
            ->get(route('tickets.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tickets/Create')
                ->where('isAdmin', false)
                ->where('isAgent', false)
                ->has('organisations', 0)
                ->has('agents', 0)
            );
    }
}

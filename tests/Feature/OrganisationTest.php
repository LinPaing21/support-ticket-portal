<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\CarbonInterface;
use Tests\TestCase;

class OrganisationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organisation_can_be_created_via_factory(): void
    {
        $organisation = Organisation::factory()->create();

        $this->assertDatabaseHas('organisations', [
            'id' => $organisation->id,
            'name' => $organisation->name,
            'short_code' => $organisation->short_code,
        ]);
    }

    public function test_organisation_joined_at_is_cast_to_datetime(): void
    {
        $organisation = Organisation::factory()->create();

        $this->assertInstanceOf(CarbonInterface::class, $organisation->joined_at);
    }

    public function test_organisation_has_many_users(): void
    {
        $organisation = Organisation::factory()->create();

        User::factory()->count(3)->create([
            'organisation_id' => $organisation->id,
            'role' => UserRole::CLIENT,
        ]);

        $this->assertCount(3, $organisation->users);
    }

    public function test_user_belongs_to_organisation(): void
    {
        $organisation = Organisation::factory()->create();

        $user = User::factory()->create([
            'organisation_id' => $organisation->id,
            'role' => UserRole::CLIENT,
        ]);

        $this->assertTrue($user->organisation->is($organisation));
    }

    public function test_user_role_is_cast_to_enum(): void
    {
        $user = User::factory()->create([
            'organisation_id' => null,
            'role' => UserRole::AGENT,
        ]);

        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertSame(UserRole::AGENT, $user->role);
    }

    public function test_agent_user_can_have_null_organisation(): void
    {
        $user = User::factory()->create([
            'organisation_id' => null,
            'role' => UserRole::AGENT,
        ]);

        $this->assertNull($user->organisation_id);
        $this->assertNull($user->organisation);
    }
}

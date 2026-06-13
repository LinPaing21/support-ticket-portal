<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Organisation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin account
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
        ]);

        // Agent accounts
        $agents = User::factory()->agent()->count(3)->create();

        // Organisations with owners, clients, and tickets
        $organisations = Organisation::factory()->count(20)->create();

        $organisations->each(function (Organisation $org) use ($agents) {
            $owner = User::factory()->organisationOwner()->create(['organisation_id' => $org->id]);

            $clients = User::factory()->count(3)->create(['organisation_id' => $org->id]);

            $submitters = $clients->push($owner);

            $tickets = Ticket::factory()->count(5)->create([
                'organisation_id' => $org->id,
                'user_id' => fn () => $submitters->random()->id,
                'assigned_agent_id' => fn () => $agents->random()->id,
            ]);

            $commenters = $submitters->merge($agents);

            $tickets->each(function (Ticket $ticket) use ($commenters) {
                Comment::factory()->count(fake()->numberBetween(2, 15))->create([
                    'ticket_id' => $ticket->id,
                    'user_id' => fn () => $commenters->random()->id,
                ]);
            });
        });
    }
}

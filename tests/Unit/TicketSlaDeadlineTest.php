<?php

namespace Tests\Unit;

use App\Enums\TicketPriority;
use App\Repositories\TicketRepository;
use App\Services\TicketService;
use Carbon\CarbonInterface;
use ReflectionMethod;
use Tests\TestCase;

class TicketSlaDeadlineTest extends TestCase
{
    private TicketService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TicketService(
            $this->createMock(TicketRepository::class),
        );
    }

    private function calculateSlaDeadline(TicketPriority $priority): CarbonInterface
    {
        $method = new ReflectionMethod(TicketService::class, 'calculateSlaDeadline');

        return $method->invoke($this->service, $priority);
    }

    public function test_urgent_deadline_is_one_hour_from_now(): void
    {
        $deadline = $this->calculateSlaDeadline(TicketPriority::URGENT);

        $this->assertEqualsWithDelta(now()->addHour()->timestamp, $deadline->timestamp, 2);
    }

    public function test_high_deadline_is_four_hours_from_now(): void
    {
        $deadline = $this->calculateSlaDeadline(TicketPriority::HIGH);

        $this->assertEqualsWithDelta(now()->addHours(4)->timestamp, $deadline->timestamp, 2);
    }

    public function test_medium_deadline_is_twelve_hours_from_now(): void
    {
        $deadline = $this->calculateSlaDeadline(TicketPriority::MEDIUM);

        $this->assertEqualsWithDelta(now()->addHours(12)->timestamp, $deadline->timestamp, 2);
    }

    public function test_low_deadline_is_two_days_from_now(): void
    {
        $deadline = $this->calculateSlaDeadline(TicketPriority::LOW);

        $this->assertEqualsWithDelta(now()->addDays(2)->timestamp, $deadline->timestamp, 2);
    }

    public function test_deadlines_are_ordered_shortest_to_longest_by_priority(): void
    {
        $urgent = $this->calculateSlaDeadline(TicketPriority::URGENT);
        $high   = $this->calculateSlaDeadline(TicketPriority::HIGH);
        $medium = $this->calculateSlaDeadline(TicketPriority::MEDIUM);
        $low    = $this->calculateSlaDeadline(TicketPriority::LOW);

        $this->assertTrue($urgent->isBefore($high));
        $this->assertTrue($high->isBefore($medium));
        $this->assertTrue($medium->isBefore($low));
    }
}

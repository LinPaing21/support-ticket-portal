<?php

use App\Enums\TableName;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(TableName::TICKETS->value, function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained(TableName::ORGANISATIONS->value)->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(TableName::USERS->value)->cascadeOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained(TableName::USERS->value)->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('status')->default(TicketStatus::OPEN->value);
            $table->string('priority')->default(TicketPriority::MEDIUM->value);
            $table->timestamp('sla_deadline');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableName::TICKETS->value);
    }
};

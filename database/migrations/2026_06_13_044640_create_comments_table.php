<?php

use App\Enums\TableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(TableName::COMMENTS->value, function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained(TableName::TICKETS->value)->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(TableName::USERS->value)->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(TableName::COMMENTS->value);
    }
};

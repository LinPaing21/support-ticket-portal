<?php

use App\Enums\TableName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(TableName::USERS->value, function (Blueprint $table) {
            $table->foreignId('organisation_id')->nullable()->after('id')->constrained(TableName::ORGANISATIONS->value)->nullOnDelete();
            $table->string('role')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table(TableName::USERS->value, function (Blueprint $table) {
            $table->dropForeign(['organisation_id']);
            $table->dropColumn(['organisation_id', 'role']);
        });
    }
};

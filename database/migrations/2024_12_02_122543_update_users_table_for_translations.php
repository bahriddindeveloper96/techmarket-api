<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Name column already removed in initial users table migration
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to add name column back
    }
};

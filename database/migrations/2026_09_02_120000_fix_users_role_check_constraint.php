<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop constraint users_role_check on PostgreSQL if it exists
        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        } catch (\Throwable $e) {
            // Ignore if constraint does not exist or not supported
        }

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 50)->change();
            });
        } catch (\Throwable $e) {
            // Ignore if string change fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

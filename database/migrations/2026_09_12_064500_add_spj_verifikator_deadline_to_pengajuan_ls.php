<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            $table->timestamp('spj_verifikator_deadline')->nullable()->after('verifikator_spm_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            $table->dropColumn('spj_verifikator_deadline');
        });
    }
};

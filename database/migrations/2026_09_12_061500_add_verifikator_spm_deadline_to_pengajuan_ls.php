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
            $table->timestamp('verifikator_spm_deadline')->nullable()->after('spj_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            $table->dropColumn('verifikator_spm_deadline');
        });
    }
};

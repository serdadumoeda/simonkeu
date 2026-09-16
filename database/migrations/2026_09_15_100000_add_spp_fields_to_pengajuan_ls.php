<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop status check constraint if exists (PostgreSQL)
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE pengajuan_ls DROP CONSTRAINT IF EXISTS pengajuan_ls_status_check");
        } catch (\Throwable $e) {}

        Schema::table('pengajuan_ls', function (Blueprint $table) {
            $table->string('no_spp')->nullable()->after('no_spm');
            $table->date('tgl_spp')->nullable()->after('no_spp');
            $table->unsignedBigInteger('spp_operator_id')->nullable()->after('tgl_spp');
            $table->string('spp_link')->nullable()->after('spp_operator_id');
            $table->string('spp_signed_link')->nullable()->after('spp_link');
            $table->timestamp('spp_signed_at')->nullable()->after('spp_signed_link');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            $table->dropColumn(['no_spp', 'tgl_spp', 'spp_operator_id', 'spp_link', 'spp_signed_link', 'spp_signed_at']);
        });
    }
};

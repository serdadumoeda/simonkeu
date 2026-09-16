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
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            if (!Schema::hasColumn('pengajuan_ls', 'catatan_spj')) {
                $table->text('catatan_spj')->nullable()->after('catatan_koreksi');
            }
            if (!Schema::hasColumn('pengajuan_ls', 'histori_catatan_json')) {
                $table->json('histori_catatan_json')->nullable()->after('catatan_spj');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            $table->dropColumn(['catatan_spj', 'histori_catatan_json']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pengajuan_banprog', 'kategori_pengajuan')) {
            Schema::table('pengajuan_banprog', function (Blueprint $table) {
                $table->string('kategori_pengajuan', 20)->default('Termin I')->after('bidang');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pengajuan_banprog', 'kategori_pengajuan')) {
            Schema::table('pengajuan_banprog', function (Blueprint $table) {
                $table->dropColumn('kategori_pengajuan');
            });
        }
    }
};

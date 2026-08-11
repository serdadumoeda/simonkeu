<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            if (!Schema::hasColumn('pengajuan_ls', 'potongan_pajak')) {
                $table->decimal('potongan_pajak', 15, 2)->default(0)->after('nilai_bruto');
            }
            if (!Schema::hasColumn('pengajuan_ls', 'data_dukung_json')) {
                $table->text('data_dukung_json')->nullable()->after('link_google_drive');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_ls', function (Blueprint $table) {
            if (Schema::hasColumn('pengajuan_ls', 'potongan_pajak')) {
                $table->dropColumn('potongan_pajak');
            }
            if (Schema::hasColumn('pengajuan_ls', 'data_dukung_json')) {
                $table->dropColumn('data_dukung_json');
            }
        });
    }
};

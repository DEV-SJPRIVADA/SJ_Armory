<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_types', function (Blueprint $table) {
            $table->boolean('is_reportable')->default(true)->after('is_active');
        });

        DB::table('incident_types')->whereIn('code', [
            'en_mantenimiento',
            'para_mantenimiento',
            'en_armerillo',
        ])->update(['is_reportable' => false]);

        DB::table('incident_types')->whereIn('code', [
            'hurtada',
            'perdida',
            'incautada',
            'dar_de_baja',
        ])->update(['is_reportable' => true]);
    }

    public function down(): void
    {
        Schema::table('incident_types', function (Blueprint $table) {
            $table->dropColumn('is_reportable');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weapon_incidents', function (Blueprint $table) {
            $table->string('closure_outcome', 100)
                ->nullable()
                ->after('resolution_note');

            $table->index(['status', 'closure_outcome'], 'weapon_incidents_status_closure_outcome_idx');
        });
    }

    public function down(): void
    {
        Schema::table('weapon_incidents', function (Blueprint $table) {
            $table->dropIndex('weapon_incidents_status_closure_outcome_idx');
            $table->dropColumn('closure_outcome');
        });
    }
};

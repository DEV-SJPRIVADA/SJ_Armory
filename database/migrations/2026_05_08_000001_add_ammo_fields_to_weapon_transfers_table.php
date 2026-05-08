<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weapon_transfers', function (Blueprint $table) {
            $table->unsignedInteger('ammo_count')->nullable()->after('note');
            $table->unsignedInteger('provider_count')->nullable()->after('ammo_count');
        });
    }

    public function down(): void
    {
        Schema::table('weapon_transfers', function (Blueprint $table) {
            $table->dropColumn(['ammo_count', 'provider_count']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weapon_import_batches', function (Blueprint $table) {
            $table->string('type')->default('weapon')->after('status');
            $table->index(['type', 'status'], 'weapon_import_batches_type_status_idx');
        });

        Schema::table('weapon_import_rows', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('weapon_id')->constrained('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('weapon_import_rows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::table('weapon_import_batches', function (Blueprint $table) {
            $table->dropIndex('weapon_import_batches_type_status_idx');
            $table->dropColumn('type');
        });
    }
};

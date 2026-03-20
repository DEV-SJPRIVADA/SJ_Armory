<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weapon_import_batches', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('executed_at');
            $table->timestamp('finished_at')->nullable()->after('started_at');
            $table->unsignedInteger('processed_rows')->default(0)->after('finished_at');
            $table->unsignedInteger('successful_rows')->default(0)->after('processed_rows');
            $table->unsignedInteger('failed_rows')->default(0)->after('successful_rows');
            $table->text('last_error')->nullable()->after('failed_rows');
        });

        Schema::table('weapon_import_rows', function (Blueprint $table) {
            $table->string('execution_status')->nullable()->after('action');
            $table->timestamp('processed_at')->nullable()->after('execution_status');
            $table->text('execution_error')->nullable()->after('processed_at');

            $table->index(['batch_id', 'execution_status']);
        });
    }

    public function down(): void
    {
        Schema::table('weapon_import_rows', function (Blueprint $table) {
            $table->dropIndex(['batch_id', 'execution_status']);
            $table->dropColumn([
                'execution_status',
                'processed_at',
                'execution_error',
            ]);
        });

        Schema::table('weapon_import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'started_at',
                'finished_at',
                'processed_rows',
                'successful_rows',
                'failed_rows',
                'last_error',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('temporary_photo_users') && ! Schema::hasColumn('temporary_photo_users', 'is_shared')) {
            Schema::table('temporary_photo_users', function (Blueprint $table) {
                $table->boolean('is_shared')->default(false)->after('email');
                $table->index(['is_shared', 'is_active'], 'tpu_shared_active_idx');
            });
        }

        if (! Schema::hasTable('temporary_photo_user_responsibles')) {
            Schema::create('temporary_photo_user_responsibles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('temporary_photo_user_id');
                $table->unsignedBigInteger('responsible_user_id');
                $table->unsignedBigInteger('assigned_by_user_id');
                $table->timestamps();

                $table->foreign('temporary_photo_user_id', 'tpur_temp_user_fk')
                    ->references('id')->on('temporary_photo_users')->cascadeOnDelete();
                $table->foreign('responsible_user_id', 'tpur_responsible_fk')
                    ->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('assigned_by_user_id', 'tpur_assigned_by_fk')
                    ->references('id')->on('users')->cascadeOnDelete();

                $table->unique(['temporary_photo_user_id', 'responsible_user_id'], 'tpur_user_responsible_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_photo_user_responsibles');

        if (Schema::hasTable('temporary_photo_users') && Schema::hasColumn('temporary_photo_users', 'is_shared')) {
            Schema::table('temporary_photo_users', function (Blueprint $table) {
                $table->dropIndex('tpu_shared_active_idx');
                $table->dropColumn('is_shared');
            });
        }
    }
};

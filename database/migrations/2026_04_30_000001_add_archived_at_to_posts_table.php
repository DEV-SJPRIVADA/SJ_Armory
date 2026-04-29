<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index('client_id');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropUnique(['client_id', 'name']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->unique(['client_id', 'name']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
        });
    }
};

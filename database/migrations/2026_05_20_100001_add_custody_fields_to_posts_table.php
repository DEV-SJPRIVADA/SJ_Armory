<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('custody_role', 40)->nullable()->after('client_id');
            $table->foreignId('owner_responsible_user_id')
                ->nullable()
                ->after('custody_role')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['owner_responsible_user_id', 'custody_role'], 'posts_owner_custody_idx');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_owner_custody_idx');
            $table->dropConstrainedForeignId('owner_responsible_user_id');
            $table->dropColumn('custody_role');
        });
    }
};

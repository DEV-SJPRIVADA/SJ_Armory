<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('weapons', 'permit_authenticated_file_id')) {
            Schema::disableForeignKeyConstraints();
            try {
                Schema::table('weapons', function (Blueprint $table) {
                    $table->dropColumn('permit_authenticated_file_id');
                });
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        }

        Schema::create('permit_authenticated_templates', function (Blueprint $table) {
            $table->id();
            $table->string('permit_kind', 20)->unique();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permit_authenticated_templates');

        Schema::table('weapons', function (Blueprint $table) {
            $table->foreignId('permit_authenticated_file_id')
                ->nullable()
                ->after('permit_file_id')
                ->constrained('files')
                ->nullOnDelete();
        });
    }
};

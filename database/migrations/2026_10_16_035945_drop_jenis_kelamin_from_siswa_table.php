<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('siswa', 'jenis_kelamin')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropColumn('jenis_kelamin');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('This migration permanently removes student gender data and cannot be rolled back.');
    }
};

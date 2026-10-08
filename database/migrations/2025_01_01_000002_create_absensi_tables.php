<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru');
            $table->date('tanggal');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa']);
            $table->text('alasan')->nullable();
            $table->string('bukti_path')->nullable();
            $table->dateTime('waktu_checkin')->nullable();
            $table->boolean('dibuat_sistem')->default(false);
            $table->enum('status_validasi', ['pending', 'tervalidasi', 'ditolak'])->default('pending');
            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users');
            $table->dateTime('divalidasi_pada')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->foreignId('dikoreksi_oleh')->nullable()->constrained('users');
            $table->dateTime('dikoreksi_pada')->nullable();
            $table->text('catatan_koreksi')->nullable();
            $table->timestamps();

            $table->unique(['guru_id', 'tanggal']);
            $table->index('tanggal');
            $table->index('status_validasi');
        });

        Schema::create('absensi_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas');
            $table->date('tanggal');
            $table->foreignId('dilaporkan_oleh')->nullable()->constrained('users');
            $table->dateTime('waktu_laporan')->nullable();
            $table->boolean('diinput_manual')->default(false);
            $table->enum('status_validasi', ['pending', 'tervalidasi', 'ditolak', 'tidak_diabsen'])->default('pending');
            $table->foreignId('divalidasi_oleh')->nullable()->constrained('users');
            $table->dateTime('divalidasi_pada')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->foreignId('dikoreksi_oleh')->nullable()->constrained('users');
            $table->dateTime('dikoreksi_pada')->nullable();
            $table->text('catatan_koreksi')->nullable();
            $table->timestamps();

            $table->unique(['kelas_id', 'tanggal']);
            $table->index('tanggal');
            $table->index('status_validasi');
        });

        Schema::create('absensi_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absensi_kelas_id')->constrained('absensi_kelas')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa');
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa']);
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['absensi_kelas_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_siswa');
        Schema::dropIfExists('absensi_kelas');
        Schema::dropIfExists('absensi_guru');
    }
};

<?php

namespace Database\Seeders;

use App\Models\AbsensiGuru;
use App\Models\AbsensiKelas;
use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\RelasiGuruKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Kalender;
use Illuminate\Database\Seeder;

/**
 * Data contoh UNTUK DEVELOPMENT. Semua password: "password".
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Kalender::sekarang();
        $mulai = $now->month >= 7 ? $now->year : $now->year - 1;

        $ta = TahunAjaran::create([
            'nama' => $mulai.'/'.($mulai + 1),
            'tanggal_mulai' => "$mulai-07-15",
            'tanggal_selesai' => ($mulai + 1).'-06-30',
            'is_aktif' => true,
        ]);

        // --- Akun ---
        User::create(['nama' => 'Administrator', 'username' => 'admin', 'email' => 'admin@sman4karawang.test',
            'password' => 'password', 'role' => 'admin', 'no_hp' => '081200000001']);

        User::create(['nama' => 'Drs. Hendra Wijaya', 'username' => 'kesiswaan1', 'email' => 'kesiswaan1@sman4karawang.test',
            'password' => 'password', 'role' => 'kesiswaan', 'jabatan' => 'Waka Kesiswaan']);
        User::create(['nama' => 'Sri Mulyani, S.Pd', 'username' => 'kesiswaan2', 'email' => 'kesiswaan2@sman4karawang.test',
            'password' => 'password', 'role' => 'kesiswaan', 'jabatan' => 'Staf Kesiswaan']);

        $namaGuru = ['Ahmad Fauzi, S.Pd', 'Siti Nurhaliza, S.Pd', 'Budi Santoso, S.Si', 'Dewi Lestari, S.Pd',
            'Rudi Hartono, S.Kom', 'Ani Rahmawati, S.Pd', 'Eko Prasetyo, M.Pd'];
        $gurus = [];
        foreach ($namaGuru as $i => $nama) {
            $n = $i + 1;
            $u = User::create(['nama' => $nama, 'username' => "guru$n", 'email' => "guru$n@sman4karawang.test",
                'password' => 'password', 'role' => 'guru', 'no_hp' => '0813000000'.$n]);
            $gurus[] = Guru::create(['user_id' => $u->id, 'nuptk' => sprintf('1234567890%06d', $n), 'status' => 'aktif']);
        }

        // --- Kelas & siswa ---
        $defKelas = [['X-1', 10], ['X-2', 10], ['XI-1', 11], ['XI-2', 11], ['XII-1', 12], ['XII-2', 12]];
        $depan = ['Aditya', 'Bagas', 'Citra', 'Dimas', 'Eka', 'Farhan', 'Gita', 'Hana', 'Indra', 'Joko', 'Kirana', 'Lutfi', 'Maya', 'Nadia', 'Oka', 'Putri', 'Rizky', 'Salsa', 'Tegar', 'Vina'];
        $belakang = ['Pratama', 'Saputra', 'Wijaya', 'Kusuma', 'Hidayat', 'Ramadhan', 'Permata', 'Nugroho', 'Lestari', 'Firmansyah'];
        $kelasList = [];
        $nis = 1;
        foreach ($defKelas as $i => [$nama, $tingkat]) {
            $kelas = Kelas::create(['tahun_ajaran_id' => $ta->id, 'nama' => $nama, 'tingkat' => $tingkat, 'ruangan' => 'R-'.(101 + $i)]);
            $kelasList[] = $kelas;
            for ($s = 0; $s < mt_rand(10, 15); $s++) {
                Siswa::create([
                    'nis' => sprintf('%d%04d', $mulai, $nis++),
                    'nama' => $depan[array_rand($depan)].' '.$belakang[array_rand($belakang)],
                    'jenis_kelamin' => mt_rand(0, 1) ? 'L' : 'P',
                    'kelas_id' => $kelas->id,
                    'status' => 'aktif',
                ]);
            }
        }

        // --- Relasi guru–kelas ---
        $mapel = ['Bahasa Indonesia', 'Matematika', 'Fisika', 'Kimia', 'Informatika', 'Sejarah', 'Bahasa Inggris'];
        foreach ($kelasList as $i => $kelas) {
            RelasiGuruKelas::create(['guru_id' => $gurus[$i]->id, 'kelas_id' => $kelas->id, 'peran' => 'wali', 'wali_key' => $kelas->id]);
        }
        foreach ($gurus as $k => $g) {
            foreach ([$k % 6, ($k + 1) % 6] as $idx) {
                RelasiGuruKelas::create(['guru_id' => $g->id, 'kelas_id' => $kelasList[$idx]->id,
                    'peran' => 'guru_mapel', 'mata_pelajaran' => $mapel[$k]]);
            }
        }

        $this->seedAbsensi($gurus, $kelasList);
    }

    /** 7 hari efektif sebelum hari ini, aneka status (termasuk ditolak & tidak_diabsen). */
    private function seedAbsensi(array $gurus, array $kelasList): void
    {
        $hari = [];
        $t = Kalender::hariIni()->subDay();
        while (count($hari) < 7) {
            if (! Kalender::libur($t)) {
                array_unshift($hari, $t->copy());
            }
            $t->subDay();
        }

        $admin = User::where('role', 'kesiswaan')->first();

        foreach ($hari as $d => $tgl) {
            $terbaru = $d === 6;
            $stamp = $tgl->copy()->setTime(7, mt_rand(0, 30));

            foreach ($gurus as $gi => $guru) {
                $r = mt_rand(1, 100);
                $status = $r <= 80 ? 'hadir' : ($r <= 90 ? 'izin' : ($r <= 97 ? 'sakit' : 'alpa'));
                $data = ['guru_id' => $guru->id, 'tanggal' => $tgl->toDateString(), 'status' => $status];

                if ($status === 'alpa') {
                    $data += ['dibuat_sistem' => true, 'status_validasi' => 'tervalidasi'];
                } else {
                    $data += ['waktu_checkin' => $stamp, 'status_validasi' => $terbaru ? 'pending' : 'tervalidasi'];
                    if ($status !== 'hadir') {
                        $data['alasan'] = $status === 'sakit' ? 'Demam, surat dokter terlampir.' : 'Menghadiri tugas dinas.';
                    }
                    if ($data['status_validasi'] === 'tervalidasi') {
                        $data += ['divalidasi_oleh' => $admin->id, 'divalidasi_pada' => $stamp->copy()->addHours(3)];
                    }
                }

                // Contoh data ditolak
                if ($gi === 1 && $d === 5) {
                    $data = array_merge($data, ['status' => 'izin', 'alasan' => 'Urusan keluarga.', 'dibuat_sistem' => false,
                        'waktu_checkin' => $stamp, 'status_validasi' => 'ditolak', 'divalidasi_oleh' => $admin->id,
                        'divalidasi_pada' => $stamp->copy()->addHours(2), 'catatan_validasi' => 'Bukti belum dilampirkan, mohon unggah ulang.']);
                }

                AbsensiGuru::create($data);
            }

            foreach ($kelasList as $ki => $kelas) {
                // Contoh kelas tidak diabsen
                if ($ki === 3 && $d === 4) {
                    AbsensiKelas::create(['kelas_id' => $kelas->id, 'tanggal' => $tgl->toDateString(), 'status_validasi' => 'tidak_diabsen']);
                    continue;
                }

                $relasi = RelasiGuruKelas::with('guru')->where('kelas_id', $kelas->id)->get()->random();
                $status = $terbaru ? 'pending' : 'tervalidasi';
                $ditolak = $ki === 2 && $d === 5;
                if ($ditolak) {
                    $status = 'ditolak';
                }

                $lap = AbsensiKelas::create([
                    'kelas_id' => $kelas->id,
                    'tanggal' => $tgl->toDateString(),
                    'dilaporkan_oleh' => $relasi->guru->user_id,
                    'waktu_laporan' => $stamp->copy()->addMinutes(20),
                    'status_validasi' => $status,
                    'divalidasi_oleh' => $status === 'pending' ? null : $admin->id,
                    'divalidasi_pada' => $status === 'pending' ? null : $stamp->copy()->addHours(3),
                    'catatan_validasi' => $ditolak ? 'Jumlah siswa sakit tidak sesuai surat. Mohon dicek ulang.' : null,
                ]);

                foreach ($kelas->siswaAktif as $s) {
                    $r = mt_rand(1, 100);
                    $st = $r <= 92 ? 'hadir' : ($r <= 95 ? 'sakit' : ($r <= 98 ? 'izin' : 'alpa'));
                    AbsensiSiswa::create([
                        'absensi_kelas_id' => $lap->id, 'siswa_id' => $s->id, 'status' => $st,
                        'keterangan' => in_array($st, ['sakit', 'izin']) ? ($st === 'sakit' ? 'Demam' : 'Keperluan keluarga') : null,
                    ]);
                }
            }
        }
    }
}

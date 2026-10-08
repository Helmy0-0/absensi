<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\AbsenSiswaRequest;
use App\Models\AbsensiKelas;
use App\Models\Kelas;
use App\Services\AbsensiKelasService;
use App\Support\Kalender;
use Illuminate\Http\Request;

class AbsenSiswaController extends Controller
{
    public function __construct(private AbsensiKelasService $service)
    {
    }

    public function index(Request $request)
    {
        $guru = $request->user()->guruOrFail();
        $hariIni = Kalender::hariIni();

        $kelasList = $guru->kelasDiampu()->orderBy('tingkat')->orderBy('nama')->get();

        // Laporan ditolak (kelas mana pun yang ia ampu, tanggal berapa pun) perlu diperbaiki.
        $ditolak = AbsensiKelas::with('kelas')
            ->whereIn('kelas_id', $guru->kelasDiampu(false)->pluck('kelas.id'))
            ->where('status_validasi', 'ditolak')
            ->orderByDesc('tanggal')
            ->get();

        $kelas = null;
        if ($request->filled('kelas_id')) {
            $kelas = Kelas::find($request->kelas_id);
            if ($kelas) {
                $this->authorize('absen', $kelas);
            }
        }
        $kelas ??= $kelasList->first();

        $data = [
            'kelasList' => $kelasList,
            'kelas' => $kelas,
            'ditolak' => $ditolak,
            'hariIni' => $hariIni,
            'tanggal' => $hariIni,
            'laporan' => null,
            'rows' => collect(),
            'mode' => 'kosong', // kosong | form | readonly | libur
        ];

        if (! $kelas) {
            return view('guru.absen-siswa', $data);
        }

        // Tanggal selain hari ini hanya diterima untuk memperbaiki laporan ditolak.
        $tanggal = Kalender::parse($request->query('tanggal'));
        $laporan = AbsensiKelas::with(['pelapor', 'detail.siswa'])
            ->where('kelas_id', $kelas->id)
            ->where('tanggal', $tanggal->toDateString())
            ->first();

        if (! $tanggal->isSameDay($hariIni) && $laporan?->status_validasi !== 'ditolak') {
            $tanggal = $hariIni;
            $laporan = AbsensiKelas::with(['pelapor', 'detail.siswa'])
                ->where('kelas_id', $kelas->id)
                ->where('tanggal', $tanggal->toDateString())
                ->first();
        }

        $data['tanggal'] = $tanggal;
        $data['laporan'] = $laporan;

        if (! $laporan && Kalender::libur($tanggal)) {
            $data['mode'] = 'libur';

            return view('guru.absen-siswa', $data);
        }

        if (! $laporan) {
            $data['mode'] = 'form';
            $data['rows'] = $kelas->siswaAktif()->orderBy('nama')->get()
                ->map(fn ($s) => ['siswa' => $s, 'status' => 'hadir', 'keterangan' => null]);
        } elseif ($laporan->status_validasi === 'ditolak') {
            $data['mode'] = 'form';
            $data['rows'] = $this->barisDariLaporan($laporan);
        } else {
            $data['mode'] = 'readonly';
            $data['rows'] = $this->barisDariLaporan($laporan);
        }

        return view('guru.absen-siswa', $data);
    }

    public function store(AbsenSiswaRequest $request)
    {
        $guru = $request->user()->guruOrFail();
        $d = $request->validated();

        $kelas = Kelas::findOrFail($d['kelas_id']);
        $this->authorize('absen', $kelas);

        $tanggal = Kalender::parse($d['tanggal']);
        $this->service->simpanOlehGuru($guru, $kelas, $tanggal, $d['siswa'] ?? []);

        return redirect()->route('guru.absen-siswa', ['kelas_id' => $kelas->id])
            ->with('success', "Absensi kelas {$kelas->nama} dikirim dan menunggu validasi.");
    }

    private function barisDariLaporan(AbsensiKelas $laporan)
    {
        return $laporan->detail
            ->sortBy(fn ($d) => mb_strtolower($d->siswa?->nama ?? ''))
            ->map(fn ($d) => ['siswa' => $d->siswa, 'status' => $d->status, 'keterangan' => $d->keterangan])
            ->values();
    }
}

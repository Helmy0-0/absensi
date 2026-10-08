<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\AbsenMandiriRequest;
use App\Models\AbsensiGuru;
use App\Services\AbsensiGuruService;
use App\Support\Kalender;
use Illuminate\Http\Request;

class AbsenMandiriController extends Controller
{
    public function __construct(private AbsensiGuruService $service)
    {
    }

    public function index(Request $request)
    {
        $guru = $request->user()->guruOrFail();
        $hariIni = Kalender::hariIni();

        $hariIniAbsen = AbsensiGuru::where('guru_id', $guru->id)
            ->where('tanggal', $hariIni->toDateString())
            ->first();

        // Absen hari ini yang ditolak otomatis membuka form perbaikan.
        $perbaikan = $hariIniAbsen?->status_validasi === 'ditolak' ? $hariIniAbsen : null;

        $ditolakLain = AbsensiGuru::where('guru_id', $guru->id)
            ->where('status_validasi', 'ditolak')
            ->when($perbaikan, fn ($q) => $q->where('id', '!=', $perbaikan->id))
            ->orderByDesc('tanggal')
            ->get();

        return view('guru.absen-mandiri', [
            'hariIni' => $hariIni,
            'libur' => Kalender::libur($hariIni),
            'absen' => $hariIniAbsen,
            'perbaikan' => $perbaikan,
            'ditolakLain' => $ditolakLain,
        ]);
    }

    public function store(AbsenMandiriRequest $request)
    {
        $guru = $request->user()->guruOrFail();
        $this->service->kirimHariIni($guru, $request->validated(), $request->file('bukti'));

        return redirect()->route('guru.absen-mandiri')
            ->with('success', 'Absen berhasil dikirim dan menunggu validasi kesiswaan.');
    }

    /** Perbaikan absen yang ditolak (boleh untuk tanggal lampau — R-32). */
    public function edit(Request $request, AbsensiGuru $absensiGuru)
    {
        $this->authorize('perbaiki', $absensiGuru);

        return view('guru.absen-mandiri', [
            'hariIni' => Kalender::hariIni(),
            'libur' => false,
            'absen' => null,
            'perbaikan' => $absensiGuru,
            'ditolakLain' => collect(),
        ]);
    }

    public function update(AbsenMandiriRequest $request, AbsensiGuru $absensiGuru)
    {
        $this->authorize('perbaiki', $absensiGuru);
        $this->service->perbaiki($absensiGuru, $request->validated(), $request->file('bukti'));

        return redirect()->route('guru.absen-mandiri')
            ->with('success', 'Perbaikan absen dikirim ulang dan menunggu validasi.');
    }
}

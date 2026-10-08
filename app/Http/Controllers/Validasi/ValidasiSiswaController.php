<?php

namespace App\Http\Controllers\Validasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\InputManualRequest;
use App\Http\Requests\KoreksiSiswaRequest;
use App\Http\Requests\TolakRequest;
use App\Models\AbsensiKelas;
use App\Models\Kelas;
use App\Services\AbsensiKelasService;
use App\Support\Kalender;
use Illuminate\Http\Request;

class ValidasiSiswaController extends Controller
{
    public function __construct(private AbsensiKelasService $service)
    {
    }

    public function index(Request $request)
    {
        $tanggal = Kalender::parse($request->query('tanggal'));
        $libur = Kalender::libur($tanggal);

        $semuaKelas = Kelas::tahunAktif()->whereHas('siswaAktif')
            ->orderBy('tingkat')->orderBy('nama')->get();

        $kelasList = $request->filled('kelas_id')
            ? $semuaKelas->where('id', (int) $request->kelas_id)
            : $semuaKelas;

        $rows = collect();
        if (! $libur) {
            $laporan = AbsensiKelas::with('pelapor')
                ->where('tanggal', $tanggal->toDateString())
                ->whereIn('kelas_id', $kelasList->pluck('id'))
                ->get()
                ->keyBy('kelas_id');

            $rows = $kelasList->map(fn ($k) => ['kelas' => $k, 'laporan' => $laporan->get($k->id)])->values();
        }

        return view('validasi.siswa.index', [
            'tanggal' => $tanggal,
            'libur' => $libur,
            'rows' => $rows,
            'semuaKelas' => $semuaKelas,
            'adaPending' => $rows->contains(fn ($r) => $r['laporan']?->status_validasi === 'pending'),
        ]);
    }

    /** Detail laporan; ?edit=1 membuka mode koreksi. */
    public function show(Request $request, AbsensiKelas $absensiKelas)
    {
        if ($absensiKelas->status_validasi === 'tidak_diabsen') {
            return redirect()->route('validasi.siswa.input-manual', [
                'kelas_id' => $absensiKelas->kelas_id,
                'tanggal' => $absensiKelas->tanggal->toDateString(),
            ]);
        }

        $absensiKelas->load(['kelas', 'pelapor', 'validator', 'korektor', 'detail.siswa']);

        $rows = $absensiKelas->detail
            ->sortBy(fn ($d) => mb_strtolower($d->siswa?->nama ?? ''))
            ->map(fn ($d) => ['siswa' => $d->siswa, 'status' => $d->status, 'keterangan' => $d->keterangan])
            ->values();

        return view('validasi.siswa.detail', [
            'mode' => $request->boolean('edit') ? 'koreksi' : 'detail',
            'laporan' => $absensiKelas,
            'kelas' => $absensiKelas->kelas,
            'tanggal' => $absensiKelas->tanggal,
            'rows' => $rows,
        ]);
    }

    public function inputManualForm(Request $request)
    {
        $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $kelas = Kelas::findOrFail($request->kelas_id);
        $tanggal = Kalender::parse($request->tanggal);

        if (Kalender::libur($tanggal)) {
            return redirect()->route('validasi.siswa')->with('error', 'Tanggal tersebut adalah hari libur.');
        }

        $laporan = AbsensiKelas::where('kelas_id', $kelas->id)->where('tanggal', $tanggal->toDateString())->first();
        if ($laporan && $laporan->status_validasi !== 'tidak_diabsen') {
            return redirect()->route('validasi.siswa.show', $laporan)
                ->with('error', 'Laporan sudah ada. Gunakan Koreksi untuk mengubahnya.');
        }

        $rows = $kelas->siswaAktif()->orderBy('nama')->get()
            ->map(fn ($s) => ['siswa' => $s, 'status' => 'hadir', 'keterangan' => null]);

        return view('validasi.siswa.detail', [
            'mode' => 'manual',
            'laporan' => $laporan,
            'kelas' => $kelas,
            'tanggal' => $tanggal,
            'rows' => $rows,
        ]);
    }

    public function inputManual(InputManualRequest $request)
    {
        $d = $request->validated();
        $tanggal = Kalender::parse($d['tanggal']);

        $this->service->inputManual(Kelas::findOrFail($d['kelas_id']), $tanggal, $request->user(), $d['siswa'] ?? []);

        return redirect()->route('validasi.siswa', ['tanggal' => $tanggal->toDateString()])
            ->with('success', 'Laporan kelas berhasil diinput manual dan langsung tervalidasi.');
    }

    public function koreksi(KoreksiSiswaRequest $request, AbsensiKelas $absensiKelas)
    {
        $d = $request->validated();
        $this->service->koreksi($absensiKelas, $request->user(), $d['siswa'] ?? [], $d['catatan_koreksi']);

        return redirect()->route('validasi.siswa.show', $absensiKelas)->with('success', 'Koreksi tersimpan.');
    }

    public function setujui(Request $request, AbsensiKelas $absensiKelas)
    {
        $this->service->setujui($absensiKelas, $request->user());

        return back()->with('success', 'Laporan kelas divalidasi.');
    }

    public function tolak(TolakRequest $request, AbsensiKelas $absensiKelas)
    {
        $this->service->tolak($absensiKelas, $request->user(), $request->validated('catatan_validasi'));

        return back()->with('success', 'Laporan kelas ditolak dan dikembalikan ke guru.');
    }

    public function setujuiSemua(Request $request)
    {
        $jumlah = $this->service->setujuiSemua(Kalender::parse($request->input('tanggal')), $request->user());

        return back()->with('success', "{$jumlah} laporan kelas divalidasi.");
    }
}

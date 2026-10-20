<?php

namespace App\Http\Controllers\Validasi;

use App\Http\Controllers\Controller;
use App\Http\Requests\KoreksiGuruRequest;
use App\Http\Requests\TolakRequest;
use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Services\AbsensiGuruService;
use App\Support\Kalender;
use Illuminate\Http\Request;

class ValidasiGuruController extends Controller
{
    public function __construct(private AbsensiGuruService $service) {}

    public function index(Request $request)
    {
        $tanggal = Kalender::parse($request->query('tanggal'));
        $libur = Kalender::libur($tanggal);
        $cari = trim((string) $request->query('q', ''));
        $filter = in_array($request->query('filter'), ['pending', 'belum_absen'], true)
            ? $request->query('filter')
            : 'semua';

        $rows = collect();
        if (! $libur) {
            $absen = AbsensiGuru::where('tanggal', $tanggal->toDateString())->get()->keyBy('guru_id');

            $rows = Guru::withTrashed()->with('user')
                ->where(function ($q) use ($absen) {
                    $q->whereIn('guru.id', $absen->keys())
                        ->orWhere(fn ($w) => $w->aktif());
                })
                ->get()
                ->sortBy(fn ($g) => mb_strtolower($g->user?->nama ?? ''))
                ->values()
                ->map(fn ($g) => ['guru' => $g, 'absen' => $absen->get($g->id)]);
        }

        $jumlahMenunggu = $rows->filter(fn ($r) => $r['absen']?->status_validasi === 'pending')->count();
        $jumlahBelumAbsen = $rows->filter(fn ($r) => $r['absen'] === null)->count();

        $rows = $rows->filter(function ($r) use ($cari, $filter) {
            $namaCocok = $cari === '' || str_contains(mb_strtolower($r['guru']->user?->nama ?? ''), mb_strtolower($cari));
            $statusCocok = match ($filter) {
                'pending' => $r['absen']?->status_validasi === 'pending',
                'belum_absen' => $r['absen'] === null,
                default => true,
            };

            return $namaCocok && $statusCocok;
        })->values();

        return view('validasi.guru.index', [
            'tanggal' => $tanggal,
            'libur' => $libur,
            'rows' => $rows,
            'adaPending' => $jumlahMenunggu > 0,
            'jumlahMenunggu' => $jumlahMenunggu,
            'jumlahBelumAbsen' => $jumlahBelumAbsen,
            'cari' => $cari,
            'filter' => $filter,
        ]);
    }

    public function setujui(Request $request, AbsensiGuru $absensiGuru)
    {
        $this->service->setujui($absensiGuru, $request->user());

        return back()->with('success', 'Absen guru disetujui.');
    }

    public function tolak(TolakRequest $request, AbsensiGuru $absensiGuru)
    {
        $this->service->tolak($absensiGuru, $request->user(), $request->validated('catatan_validasi'));

        return back()->with('success', 'Absen guru ditolak dan dikembalikan ke guru untuk diperbaiki.');
    }

    public function setujuiSemua(Request $request)
    {
        $jumlah = $this->service->setujuiSemua(Kalender::parse($request->input('tanggal')), $request->user());

        return back()->with('success', "{$jumlah} absen guru disetujui.");
    }

    public function koreksi(KoreksiGuruRequest $request)
    {
        $data = $request->validated();
        $guru = Guru::withTrashed()->findOrFail($data['guru_id']);

        $this->service->koreksi(
            $guru,
            Kalender::parse($data['tanggal']),
            $request->user(),
            $data,
            $request->file('bukti')
        );

        return back()->with('success', 'Koreksi absen guru tersimpan.');
    }
}

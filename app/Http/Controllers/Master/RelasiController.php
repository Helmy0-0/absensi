<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\RelasiRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\RelasiGuruKelas;
use App\Services\RelasiService;
use Illuminate\Http\Request;

class RelasiController extends Controller
{
    public function __construct(private RelasiService $service)
    {
    }

    public function index(Request $request)
    {
        $relasi = RelasiGuruKelas::with(['guru.user', 'kelas.tahunAjaran'])
            ->when($request->filled('kelas_id'), fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->orderBy('kelas_id')->orderByDesc('peran')
            ->paginate(20)
            ->withQueryString();

        return view('master.relasi.index', [
            'relasi' => $relasi,
            'kelasList' => Kelas::tahunAktif()->orderBy('tingkat')->orderBy('nama')->get(),
        ]);
    }

    public function create()
    {
        return view('master.relasi.form', ['relasi' => null] + $this->pilihan());
    }

    public function store(RelasiRequest $request)
    {
        $this->service->simpan($request->validated());

        return redirect()->route('master.relasi.index')->with('success', 'Relasi guru–kelas berhasil ditambahkan.');
    }

    public function edit(RelasiGuruKelas $relasi)
    {
        return view('master.relasi.form', ['relasi' => $relasi] + $this->pilihan($relasi));
    }

    public function update(RelasiRequest $request, RelasiGuruKelas $relasi)
    {
        $this->service->simpan($request->validated(), $relasi);

        return redirect()->route('master.relasi.index')->with('success', 'Relasi guru–kelas berhasil diperbarui.');
    }

    public function destroy(RelasiGuruKelas $relasi)
    {
        $this->service->hapus($relasi);

        return redirect()->route('master.relasi.index')->with('success', 'Relasi guru–kelas berhasil dihapus.');
    }

    /** Guru hanya yang aktif, kelas hanya tahun ajaran aktif (kecuali yang sedang diedit). */
    private function pilihan(?RelasiGuruKelas $relasi = null): array
    {
        $guru = Guru::with('user')
            ->where(function ($q) use ($relasi) {
                $q->aktif();
                if ($relasi) {
                    $q->orWhere('guru.id', $relasi->guru_id);
                }
            })
            ->get()
            ->sortBy(fn ($g) => mb_strtolower($g->user?->nama ?? ''))
            ->values();

        $kelas = Kelas::where(function ($q) use ($relasi) {
            $q->tahunAktif();
            if ($relasi) {
                $q->orWhere('kelas.id', $relasi->kelas_id);
            }
        })->orderBy('tingkat')->orderBy('nama')->get();

        return ['guruList' => $guru, 'kelasList' => $kelas];
    }
}

<?php

namespace App\Http\Controllers\Master;

use App\Exceptions\BisnisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\KelasRequest;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $tahunAktif = TahunAjaran::aktif();
        $tahunId = $request->input('tahun_ajaran_id', $tahunAktif?->id);

        $kelas = Kelas::with('tahunAjaran')
            ->withCount('siswaAktif')
            ->when($tahunId, fn ($q) => $q->where('tahun_ajaran_id', $tahunId))
            ->when($request->filled('q'), fn ($q) => $q->where('nama', 'like', '%'.$request->q.'%'))
            ->when($request->filled('tingkat'), fn ($q) => $q->where('tingkat', $request->tingkat))
            ->orderBy('tingkat')->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('master.kelas.index', [
            'kelas' => $kelas,
            'tahunList' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunId' => $tahunId,
        ]);
    }

    public function create()
    {
        return view('master.kelas.form', ['kelas' => null, 'tahunList' => TahunAjaran::orderByDesc('tanggal_mulai')->get()]);
    }

    public function store(KelasRequest $request)
    {
        Kelas::create($request->validated());

        return redirect()->route('master.kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function show(Kelas $kelas)
    {
        $kelas->load('tahunAjaran');
        $siswa = $kelas->siswa()->orderBy('nama')->get();

        return view('master.kelas.show', compact('kelas', 'siswa'));
    }

    public function edit(Kelas $kelas)
    {
        return view('master.kelas.form', ['kelas' => $kelas, 'tahunList' => TahunAjaran::orderByDesc('tanggal_mulai')->get()]);
    }

    public function update(KelasRequest $request, Kelas $kelas)
    {
        $kelas->update($request->validated());

        return redirect()->route('master.kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    /** R-70: kelas yang masih punya siswa aktif tidak bisa dihapus. */
    public function destroy(Kelas $kelas)
    {
        if ($kelas->siswaAktif()->exists()) {
            throw new BisnisException('Kelas masih memiliki siswa aktif dan tidak dapat dihapus.');
        }

        $kelas->delete();

        return redirect()->route('master.kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }
}

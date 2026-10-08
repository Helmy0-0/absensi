<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportSiswaRequest;
use App\Http\Requests\SiswaRequest;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\SiswaImportService;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $siswa = Siswa::with('kelas')
            ->when($request->filled('q'), function ($q) use ($request) {
                $kata = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('nama', 'like', $kata)->orWhere('nis', 'like', $kata));
            })
            ->when($request->filled('kelas_id'), fn ($q) => $q->where('kelas_id', $request->kelas_id))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('master.siswa.index', [
            'siswa' => $siswa,
            'kelasList' => $this->kelasAktif(),
        ]);
    }

    public function create()
    {
        return view('master.siswa.form', ['siswa' => null, 'kelasList' => $this->kelasAktif()]);
    }

    public function store(SiswaRequest $request)
    {
        Siswa::create($request->validated());

        return redirect()->route('master.siswa.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function edit(Siswa $siswa)
    {
        return view('master.siswa.form', ['siswa' => $siswa, 'kelasList' => $this->kelasAktif($siswa->kelas_id)]);
    }

    public function update(SiswaRequest $request, Siswa $siswa)
    {
        $siswa->update($request->validated());

        return redirect()->route('master.siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /** R-03/R-71: soft delete — riwayat absensi tetap utuh. */
    public function destroy(Siswa $siswa)
    {
        $siswa->delete();

        return redirect()->route('master.siswa.index')->with('success', 'Siswa berhasil dihapus.');
    }

    public function import(ImportSiswaRequest $request, SiswaImportService $service)
    {
        $laporan = $service->import($request->file('file'));

        return redirect()->route('master.siswa.index')->with('laporan_import', $laporan);
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, SiswaImportService::HEADER);
            fputcsv($out, ['2026001', 'Contoh Nama Siswa', 'L', 'X-1']);
            fclose($out);
        }, 'template-import-siswa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function kelasAktif(?int $sertakanId = null)
    {
        return Kelas::tahunAktif()
            ->when($sertakanId, fn ($q) => $q->orWhere('kelas.id', $sertakanId))
            ->orderBy('tingkat')->orderBy('nama')->get();
    }
}

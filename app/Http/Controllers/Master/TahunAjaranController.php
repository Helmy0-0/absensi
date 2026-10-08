<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\TahunAjaranRequest;
use App\Models\TahunAjaran;
use App\Services\TahunAjaranService;

class TahunAjaranController extends Controller
{
    public function __construct(private TahunAjaranService $service)
    {
    }

    public function index()
    {
        return view('master.tahun-ajaran.index', [
            'tahunList' => TahunAjaran::withCount('kelas')->orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function store(TahunAjaranRequest $request)
    {
        $this->service->simpan($request->validated());

        return back()->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function update(TahunAjaranRequest $request, TahunAjaran $tahunAjaran)
    {
        $this->service->simpan($request->validated(), $tahunAjaran);

        return back()->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function aktifkan(TahunAjaran $tahunAjaran)
    {
        $this->service->tetapkanAktif($tahunAjaran);

        return back()->with('success', "Tahun ajaran {$tahunAjaran->nama} ditetapkan sebagai aktif.");
    }
}

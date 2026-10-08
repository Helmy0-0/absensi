<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    /** Arahkan ke dashboard sesuai role. */
    public function redirect(Request $request)
    {
        return redirect()->route($request->user()->role.'.dashboard');
    }

    public function admin()
    {
        return view('dashboard', [
            'kartu' => $this->dashboard->kartuAdmin(),
            'grafik' => $this->dashboard->grafik(denganGuru: true),
            'peringatan' => null,
            'ditolak' => null,
        ]);
    }

    public function kesiswaan()
    {
        $tidakDiabsen = $this->dashboard->jumlahKelasTidakDiabsen();

        return view('dashboard', [
            'kartu' => $this->dashboard->kartuKesiswaan(),
            'grafik' => $this->dashboard->grafik(denganGuru: true),
            'peringatan' => $tidakDiabsen > 0
                ? "Ada {$tidakDiabsen} laporan kelas berstatus TIDAK DIABSEN yang belum diisi."
                : null,
            'ditolak' => null,
        ]);
    }

    public function guru(Request $request)
    {
        $guru = $request->user()->guruOrFail();
        $kelasIds = $guru->kelasDiampu()->pluck('kelas.id')->all();

        return view('dashboard', [
            'kartu' => $this->dashboard->kartuGuru($guru),
            'grafik' => $this->dashboard->grafik(denganGuru: false, kelasIds: $kelasIds),
            'peringatan' => null,
            'ditolak' => $this->dashboard->ditolakUntukGuru($guru),
        ]);
    }
}

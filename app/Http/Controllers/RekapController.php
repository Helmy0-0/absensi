<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Services\RekapService;
use App\Support\Kalender;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Dipakai bersama: Admin/Kesiswaan (/rekap) dan Guru (/guru/rekap).
 * Pembatasan guru (hanya siswa & kelas yang diampu) ditegakkan di sini, di sisi server.
 */
class RekapController extends Controller
{
    public function __construct(private RekapService $service)
    {
    }

    public function index(Request $request)
    {
        $f = $this->filter($request);

        return view('rekap.index', [
            'f' => $f,
            'rows' => $f['rows'],
            'kelasList' => $f['kelasList'],
            'rute' => $this->rute($request),
            'adalahGuru' => $request->user()->isGuru(),
        ]);
    }

    public function pdf(Request $request)
    {
        $f = $this->filter($request);

        $pdf = Pdf::loadView('rekap.pdf', [
            'f' => $f,
            'rows' => $f['rows'],
            'sekolah' => config('absensi.nama_sekolah'),
            'alamat' => config('absensi.alamat_sekolah'),
            'dicetak' => Kalender::tanggalPanjang(Kalender::sekarang()).' '.Kalender::sekarang()->format('H:i').' WIB',
        ])->setPaper('a4', 'portrait');

        return $pdf->download($this->namaFile($f).'.pdf');
    }

    public function csv(Request $request)
    {
        $f = $this->filter($request);

        return response()->streamDownload(function () use ($f) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar rapi di Excel
            fputcsv($out, ['No', $f['tipe'] === 'guru' ? 'NUPTK' : 'NIS', 'Nama', 'Hadir', 'Sakit', 'Izin', 'Alpa']);
            foreach ($f['rows'] as $i => $r) {
                fputcsv($out, [$i + 1, $r['kode'], $r['nama'], $r['hadir'], $r['sakit'], $r['izin'], $r['alpa']]);
            }
            fclose($out);
        }, $this->namaFile($f).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Membaca & memvalidasi filter, lalu menghitung rekap sesuai hak akses. */
    private function filter(Request $request): array
    {
        $user = $request->user();
        $adalahGuru = $user->isGuru();

        $tipe = $adalahGuru ? 'siswa' : ($request->input('tipe') === 'guru' ? 'guru' : 'siswa');

        $default = Kalender::hariIni()->startOfMonth();
        $dari = Kalender::parseBulan($request->input('dari'), $default)->startOfMonth();
        $sampaiBulan = Kalender::parseBulan($request->input('sampai'), $dari);
        if ($sampaiBulan->lt($dari)) {
            $sampaiBulan = $dari->copy();
        }
        $sampai = $sampaiBulan->copy()->endOfMonth();

        // Daftar kelas yang boleh dipilih
        if ($adalahGuru) {
            $kelasList = $user->guruOrFail()->kelasDiampu(false)->with('tahunAjaran')
                ->orderBy('tingkat')->orderBy('nama')->get();
            $kelasId = $kelasList->pluck('id')->contains((int) $request->input('kelas_id'))
                ? (int) $request->input('kelas_id')
                : $kelasList->first()?->id;
            $batas = $kelasList->pluck('id')->all();
        } else {
            $kelasList = Kelas::with('tahunAjaran')->orderByDesc('tahun_ajaran_id')->orderBy('tingkat')->orderBy('nama')->get();
            $kelasId = $request->filled('kelas_id') ? (int) $request->input('kelas_id') : null;
            $batas = null;
        }

        if ($tipe === 'guru') {
            $rows = $this->service->guru($dari, $sampai);
            $labelKelas = null;
        } else {
            $rows = ($adalahGuru && ! $kelasId)
                ? collect()
                : $this->service->siswa($dari, $sampai, $kelasId, $batas);
            $labelKelas = $kelasId ? ($kelasList->firstWhere('id', $kelasId)?->nama ?? '-') : 'Semua Kelas';
        }

        return [
            'tipe' => $tipe,
            'dari' => $dari,
            'sampai' => $sampaiBulan,
            'kelas_id' => $kelasId,
            'label_kelas' => $labelKelas,
            'periode' => $dari->eq($sampaiBulan)
                ? Kalender::bulanTahun($dari)
                : Kalender::bulanTahun($dari).' s.d. '.Kalender::bulanTahun($sampaiBulan),
            'judul' => $tipe === 'guru' ? 'Rekap Absensi Guru' : 'Rekap Absensi Siswa',
            'rows' => $rows->values(),
            'kelasList' => $kelasList,
        ];
    }

    private function namaFile(array $f): string
    {
        $bagian = ['rekap', $f['tipe']];
        if ($f['label_kelas']) {
            $bagian[] = str($f['label_kelas'])->slug()->toString();
        }
        $bagian[] = $f['dari']->format('Ym').'-'.$f['sampai']->format('Ym');

        return implode('-', $bagian);
    }

    private function rute(Request $request): array
    {
        return $request->user()->isGuru()
            ? ['index' => 'guru.rekap', 'pdf' => 'guru.rekap.pdf', 'csv' => 'guru.rekap.csv']
            : ['index' => 'rekap', 'pdf' => 'rekap.pdf', 'csv' => 'rekap.csv'];
    }
}

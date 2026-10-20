<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use App\Services\MonitoringIndividuService;
use App\Support\Kalender;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MonitoringIndividuController extends Controller
{
    public function index(Request $request, MonitoringIndividuService $service)
    {
        return view('monitoring-individu.index', $this->data($request, $service));
    }

    public function pdf(Request $request, MonitoringIndividuService $service)
    {
        $data = $this->data($request, $service);
        abort_unless($data['person'], 404);

        $pdf = Pdf::loadView('monitoring-individu.pdf', [
            ...$data,
            'sekolah' => config('absensi.nama_sekolah'),
            'alamat' => config('absensi.alamat_sekolah'),
            'dicetak' => Kalender::tanggalPanjang(Kalender::sekarang()).' '.Kalender::sekarang()->format('H:i').' WIB',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('monitoring-'.str($data['namaPerson'])->slug().'-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'.pdf');
    }

    /** @return array<string,mixed> */
    private function data(Request $request, MonitoringIndividuService $service): array
    {
        $validated = $request->validate([
            'tipe' => ['nullable', Rule::in(['siswa', 'guru'])],
            'person_id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'periode' => ['nullable', Rule::in(['mingguan', 'bulanan', 'tahun_ajaran', 'custom'])],
            'skala_tren' => ['nullable', Rule::in(['minggu', 'bulan', 'semester', 'tahun'])],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $user = $request->user();
        $tipe = $validated['tipe'] ?? 'siswa';
        $search = trim($validated['q'] ?? '');
        $periode = $validated['periode'] ?? 'mingguan';
        $skalaTren = $validated['skala_tren'] ?? 'minggu';
        [$dari, $sampai] = $this->periode($periode, $validated);
        $kelasIds = $user->isGuru() ? $user->guruOrFail()->kelasDiampu()->pluck('kelas.id')->all() : null;
        $daftar = $tipe === 'siswa'
            ? $service->daftarSiswa($kelasIds, $search)
            : $service->daftarGuru($user, $search);

        $person = null;
        $statistik = null;
        if (! empty($validated['person_id'])) {
            $person = $daftar->firstWhere('id', (int) $validated['person_id']);
            abort_unless($person, 403, 'Anda tidak memiliki akses ke data individu ini.');
            $statistik = $tipe === 'siswa'
                ? $service->siswa($person, $dari, $sampai, $skalaTren)
                : $service->guru($person, $dari, $sampai, $skalaTren);
            $statistik['trend'] = $tipe === 'siswa'
                ? $service->trenSiswa($person, $skalaTren, $sampai)
                : $service->trenGuru($person, $skalaTren, $sampai);
        }

        $rute = $user->isGuru()
            ? ['index' => 'guru.monitoring-individu', 'pdf' => 'guru.monitoring-individu.pdf']
            : ['index' => 'monitoring-individu', 'pdf' => 'monitoring-individu.pdf'];

        return [
            'tipe' => $tipe,
            'search' => $search,
            'periode' => $periode,
            'skalaTren' => $skalaTren,
            'periodeLabel' => $this->labelPeriode($periode, $dari, $sampai),
            'dari' => $dari,
            'sampai' => $sampai,
            'person' => $person,
            'namaPerson' => $person
                ? ($tipe === 'siswa' ? $person->nama : ($person->user?->nama ?? '(tanpa nama)'))
                : null,
            'daftar' => $daftar,
            'statistik' => $statistik,
            'rute' => $rute,
            'adalahGuru' => $user->isGuru(),
            'chart' => $statistik ? [
                'labels' => ['Hadir', 'Sakit', 'Izin', 'Alpa'],
                'counts' => array_values($statistik['status']),
                'trendLabels' => array_column($statistik['trend'], 'label'),
                'trendValues' => array_column($statistik['trend'], 'percent'),
                'trendHadir' => array_column($statistik['trend'], 'hadir'),
                'trendTotal' => array_column($statistik['trend'], 'total'),
            ] : null,
        ];
    }

    /**
     * @param  array<string,mixed>  $validated
     * @return array{0:Carbon,1:Carbon}
     */
    private function periode(string $periode, array $validated): array
    {
        $hariIni = Kalender::hariIni();

        if ($periode === 'mingguan') {
            $hariEfektif = Kalender::hariEfektifTerakhir(7);

            return [$hariEfektif[0], $hariIni];
        }

        if ($periode === 'bulanan') {
            return [$hariIni->copy()->startOfMonth(), $hariIni];
        }

        if ($periode === 'tahun_ajaran') {
            $tahunAjaran = TahunAjaran::where('is_aktif', true)->first();
            if ($tahunAjaran) {
                $mulai = Carbon::parse($tahunAjaran->tanggal_mulai, Kalender::tz())->startOfDay();
                $akhirAjaran = Carbon::parse($tahunAjaran->tanggal_selesai, Kalender::tz())->startOfDay();

                return [$mulai, $hariIni->lt($akhirAjaran) ? $hariIni : $akhirAjaran];
            }

            return [$hariIni->copy()->startOfYear(), $hariIni];
        }

        if (empty($validated['dari']) || empty($validated['sampai'])) {
            throw ValidationException::withMessages([
                'dari' => 'Isi tanggal awal dan akhir untuk rentang khusus.',
            ]);
        }

        $dari = Carbon::createFromFormat('!Y-m-d', $validated['dari'], Kalender::tz());
        $sampai = Carbon::createFromFormat('!Y-m-d', $validated['sampai'], Kalender::tz());
        if ($dari->gt($sampai)) {
            throw ValidationException::withMessages([
                'sampai' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
            ]);
        }

        return [$dari, $sampai];
    }

    private function labelPeriode(string $periode, Carbon $dari, Carbon $sampai): string
    {
        if ($periode === 'mingguan') {
            return '7 hari efektif terakhir';
        }
        if ($periode === 'bulanan') {
            return 'Bulan ini';
        }
        if ($periode === 'tahun_ajaran') {
            return 'Tahun ajaran aktif';
        }

        return Kalender::tanggalPanjang($dari).' s.d. '.Kalender::tanggalPanjang($sampai);
    }
}

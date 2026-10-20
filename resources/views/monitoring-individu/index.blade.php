@extends('layouts.app')
@section('judul', 'Monitoring Individu')
@section('isi')
<div class="card">
    <h2>Monitoring Absensi Individu</h2>
    <p class="mut sm" style="margin-top:0">Rekap resmi hanya memakai absensi yang tervalidasi. Hari tanpa catatan tidak dianggap alpa. Sabtu/Minggu dikecualikan; libur khusus sekolah belum tercatat di kalender aplikasi.</p>

    <form method="GET" action="{{ route($rute['index']) }}" class="filters" x-data="{ periode: '{{ $periode }}' }">
        <div>
            <label for="tipe">Jenis data</label>
            <select id="tipe" name="tipe" onchange="this.form.q.value = ''; this.form.person_id.value = ''; this.form.submit()">
                <option value="siswa" @selected($tipe === 'siswa')>Siswa</option>
                <option value="guru" @selected($tipe === 'guru')>Guru</option>
            </select>
        </div>
        <div class="monitor-search">
            <label for="q">Cari {{ $tipe === 'siswa' ? 'nama lengkap atau NIS' : 'nama atau NUPTK' }}</label>
            <input id="q" type="search" name="q" value="{{ $search }}" minlength="2" maxlength="100" placeholder="Ketik minimal 2 karakter" oninput="this.form.person_id.value = ''">
        </div>
        <div class="fix"><button class="btn sec" name="aksi" value="cari" formnovalidate>Cari</button></div>
        <div class="monitor-person">
            <label for="person_id">Pilih {{ $tipe === 'siswa' ? 'siswa' : 'guru' }}</label>
            <select id="person_id" name="person_id" required>
                <option value="">{{ mb_strlen($search) >= 2 || $adalahGuru && $tipe === 'guru' ? 'Pilih hasil pencarian' : 'Cari dahulu untuk menampilkan nama' }}</option>
                @foreach ($daftar as $pilihan)
                    @php
                        $namaPilihan = $tipe === 'siswa' ? $pilihan->nama : ($pilihan->user?->nama ?? '(tanpa nama)');
                        $kodePilihan = $tipe === 'siswa' ? $pilihan->nis : ($pilihan->nuptk ?: 'Tanpa NUPTK');
                        $kelasPilihan = $tipe === 'siswa' ? ($pilihan->kelas?->nama ?? 'Tanpa kelas') : null;
                    @endphp
                    <option value="{{ $pilihan->id }}" @selected((string) request('person_id') === (string) $pilihan->id)>
                        {{ $namaPilihan }} · {{ $kodePilihan }}@if ($kelasPilihan) · {{ $kelasPilihan }}@endif
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="periode">Rentang waktu</label>
            <select id="periode" name="periode" x-model="periode">
                <option value="mingguan">7 hari efektif terakhir</option>
                <option value="bulanan">Bulan ini</option>
                <option value="tahun_ajaran">Tahun ajaran aktif</option>
                <option value="custom">Pilih tanggal</option>
            </select>
        </div>
        <div x-show="periode === 'custom'" x-cloak>
            <label for="dari">Dari tanggal</label>
            <input id="dari" type="date" name="dari" value="{{ request('dari', $dari->toDateString()) }}" :required="periode === 'custom'">
        </div>
        <div x-show="periode === 'custom'" x-cloak>
            <label for="sampai">Sampai tanggal</label>
            <input id="sampai" type="date" name="sampai" value="{{ request('sampai', $sampai->toDateString()) }}" :required="periode === 'custom'">
        </div>
        <div class="fix"><button class="btn" name="aksi" value="tampilkan">Tampilkan</button></div>
    </form>

    @if ($adalahGuru && $tipe === 'guru')
        <p class="mut sm">Data guru yang ditampilkan adalah absensi akun Anda sendiri.</p>
    @elseif (mb_strlen($search) < 2)
        <p class="mut sm">Ketik minimal 2 karakter pada nama atau NIS, lalu tekan Cari. Maksimal 100 hasil ditampilkan.</p>
    @elseif ($daftar->isEmpty())
        <div class="alert alert-warn">Tidak ada hasil yang cocok. Periksa kembali nama atau NIS.</div>
    @else
        <p class="mut sm">Menampilkan {{ $daftar->count() }} hasil. Maksimal 100 hasil ditampilkan; perjelas pencarian bila belum menemukan orangnya.</p>
    @endif

    @if ($adalahGuru && $tipe === 'siswa' && $daftar->isEmpty())
        <div class="alert alert-warn">Anda belum terdaftar mengampu kelas pada tahun ajaran aktif.</div>
    @endif
</div>

@if ($person && $statistik)
    @php
        $nama = $tipe === 'siswa' ? $person->nama : ($person->user?->nama ?? '(tanpa nama)');
        $kode = $tipe === 'siswa' ? $person->nis : ($person->nuptk ?: 'Tanpa NUPTK');
        $kelas = $tipe === 'siswa' ? $person->kelas?->nama : null;
    @endphp
    <div class="card flex between">
        <div>
            <h2>{{ $nama }}</h2>
            <div class="mut sm">{{ $tipe === 'siswa' ? 'NIS' : 'NUPTK' }}: {{ $kode }}@if ($kelas) · Kelas {{ $kelas }}@endif</div>
            <div class="mut sm">Periode: {{ $periodeLabel }} · {{ $dari->format('d/m/Y') }} s.d. {{ $sampai->format('d/m/Y') }}</div>
        </div>
        <a class="btn sec" href="{{ route($rute['pdf'], request()->query()) }}" target="_blank">Unduh PDF</a>
    </div>

    <div class="card">
        <div class="flex between mb">
            <h2 style="margin:0">Total Absensi</h2>
            <span class="mut sm">{{ $statistik['total'] }} catatan tervalidasi</span>
        </div>
        <div class="grid g4 attendance-counts">
            @foreach ([['hadir', 'Hadir'], ['alpa', 'Alpa'], ['sakit', 'Sakit'], ['izin', 'Izin']] as [$kunci, $label])
                <div class="stat"><div class="l">{{ $label }}</div><div class="v">{{ $statistik['status'][$kunci] }}</div></div>
            @endforeach
        </div>
    </div>

    <div class="grid g2 mb">
        <div class="stat"><div class="l">Persentase hadir</div><div class="v">{{ $statistik['hadir_percent'] !== null ? $statistik['hadir_percent'].'%' : '—' }}</div><div class="h">Dari absensi yang tercatat</div></div>
        <div class="stat"><div class="l">Hari tercatat</div><div class="v">{{ $statistik['hari_tercatat'] }}</div><div class="h">Hari efektif dengan absensi tervalidasi</div></div>
    </div>

    @if ($statistik['total'] === 0)
        <div class="alert alert-warn">Belum ada absensi tervalidasi pada rentang ringkasan ini. Hari tanpa catatan tidak dihitung sebagai alpa.</div>
    @endif

    <div class="grid g2 monitoring-charts">
        <div class="card">
            <h2>Komposisi Absensi</h2>
            <div class="monitor-chart"><canvas id="komposisi"></canvas></div>
        </div>
        <div class="card">
            <h2>Tren Persentase Hadir</h2>
            <p class="mut sm" style="margin-top:0">Menampilkan {{ ['minggu' => '8 minggu', 'bulan' => '12 bulan', 'semester' => '6 semester', 'tahun' => '5 tahun'][$skalaTren] }} hingga {{ $sampai->format('d/m/Y') }}. Hanya absensi tervalidasi; arahkan ke titik untuk melihat hadir dibanding jumlah catatan.</p>
            <form method="GET" action="{{ route($rute['index']) }}" class="filters trend-filters">
                <input type="hidden" name="tipe" value="{{ $tipe }}">
                <input type="hidden" name="q" value="{{ $search }}">
                <input type="hidden" name="person_id" value="{{ $person->id }}">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <input type="hidden" name="dari" value="{{ $dari->toDateString() }}">
                <input type="hidden" name="sampai" value="{{ $sampai->toDateString() }}">
                <div>
                    <label for="skala_tren">Kelompok tren</label>
                    <select id="skala_tren" name="skala_tren">
                        <option value="minggu" @selected($skalaTren === 'minggu')>Per minggu</option>
                        <option value="bulan" @selected($skalaTren === 'bulan')>Per bulan</option>
                        <option value="semester" @selected($skalaTren === 'semester')>Per semester</option>
                        <option value="tahun" @selected($skalaTren === 'tahun')>Per tahun</option>
                    </select>
                </div>
                <div class="fix"><button class="btn sec" type="submit">Perbarui tren</button></div>
            </form>
            <div class="monitor-chart"><canvas id="tren"></canvas></div>
        </div>
    </div>

    <div class="card">
        <h2>Riwayat Ketidakhadiran</h2>
        <p class="mut sm">Hanya menampilkan sakit, izin, dan alpa yang sudah tervalidasi pada periode ini.</p>
        <div class="tw">
            <table class="tbl">
                <thead><tr><th>Tanggal</th><th>Hari</th><th>Status</th><th>Keterangan</th></tr></thead>
                <tbody>
                @forelse ($statistik['ketidakhadiran'] as $baris)
                    <tr>
                        <td data-label="Tanggal">{{ $baris['tanggal'] }}</td>
                        <td data-label="Hari">{{ $baris['hari'] }}</td>
                        <td data-label="Status"><x-badge :status="$baris['status']" /></td>
                        <td data-label="Keterangan">{{ $baris['alasan'] ?: 'Tidak ada keterangan tercatat.' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Tidak ada ketidakhadiran tervalidasi pada periode ini.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@if ($chart)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        Chart.defaults.font.family = "Poppins, system-ui, -apple-system, 'Segoe UI', sans-serif";
        Chart.defaults.font.size = 14;
        const monitoringData = @json($chart);
        const compositionCanvas = document.getElementById('komposisi');
        const trendCanvas = document.getElementById('tren');
        new Chart(compositionCanvas, {
            type: 'doughnut',
            data: {
                labels: monitoringData.labels,
                datasets: [{ data: monitoringData.counts, backgroundColor: ['#15803d', '#a16207', '#1d4ed8', '#b91c1c'] }],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: monitoringData.trendLabels,
                datasets: [{
                    label: 'Hadir (%)', data: monitoringData.trendValues,
                    borderColor: '#6d28d9', backgroundColor: '#6d28d933', tension: .3,
                    spanGaps: false, pointRadius: 4,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { min: 0, max: 100, ticks: { callback: value => value + '%' } } },
                plugins: { tooltip: { callbacks: { label: context => context.parsed.y === null
                    ? 'Belum ada catatan tervalidasi'
                    : `Hadir ${monitoringData.trendHadir[context.dataIndex]} dari ${monitoringData.trendTotal[context.dataIndex]} hari tercatat (${context.parsed.y}%)` } } },
            },
        });
    </script>
    @endpush
@endif

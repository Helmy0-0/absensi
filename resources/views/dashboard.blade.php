@extends('layouts.app')
@section('judul', 'Dashboard')
@section('isi')

@if ($peringatan)
    <div class="alert alert-warn flex between">
        <span>⚠ {{ $peringatan }}</span>
        <a class="btn sm" href="{{ route('validasi.siswa') }}">Buka Validasi Siswa</a>
    </div>
@endif

@if ($ditolak && ($ditolak['absen']->isNotEmpty() || $ditolak['laporan']->isNotEmpty()))
    <div class="alert alert-err">
        <strong>Ada data yang ditolak dan perlu Anda perbaiki:</strong>
        <ul>
            @foreach ($ditolak['absen'] as $a)
                <li>
                    Absen mandiri {{ $a->tanggal->format('d/m/Y') }} —
                    alasan: <em>{{ $a->catatan_validasi }}</em>
                    · <a href="{{ route('guru.absen-mandiri.edit', $a) }}">Perbaiki</a>
                </li>
            @endforeach
            @foreach ($ditolak['laporan'] as $l)
                <li>
                    Laporan kelas {{ $l->kelas?->nama }} ({{ $l->tanggal->format('d/m/Y') }}) —
                    alasan: <em>{{ $l->catatan_validasi }}</em>
                    · <a href="{{ route('guru.absen-siswa', ['kelas_id' => $l->kelas_id, 'tanggal' => $l->tanggal->toDateString()]) }}">Perbaiki</a>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid {{ count($kartu) >= 4 ? 'g4' : 'g3' }} mb">
    @foreach ($kartu as $k)
        <x-stat :label="$k['label']" :value="$k['value']" :hint="$k['hint'] ?? null" />
    @endforeach
</div>

<div class="card">
    <h2>Persentase Kehadiran — 7 Hari Efektif Terakhir</h2>
    <p class="mut sm" style="margin-top:0">
        Menghitung data menunggu validasi + tervalidasi. Sabtu/Minggu tidak dihitung.
        @if (is_null($grafik['guru'])) Menampilkan siswa pada kelas yang Anda ampu. @endif
    </p>
    <div style="position:relative;height:300px"><canvas id="grafik"></canvas></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const g = @json($grafik);
    const dataset = (label, data, warna) => ({
        label, data, borderColor: warna, backgroundColor: warna + '33',
        tension: .3, spanGaps: true, pointRadius: 4, fill: false,
    });
    const ds = [];
    if (g.guru) ds.push(dataset('Guru (%)', g.guru, '#6d28d9'));
    ds.push(dataset('Siswa (%)', g.siswa, '#059669'));
    new Chart(document.getElementById('grafik'), {
        type: 'line',
        data: { labels: g.labels, datasets: ds },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { min: 0, max: 100, ticks: { callback: v => v + '%' } } },
            plugins: { tooltip: { callbacks: { label: c => c.dataset.label.replace(' (%)', '') + ': ' + c.parsed.y + '%' } } },
        },
    });
</script>
@endpush

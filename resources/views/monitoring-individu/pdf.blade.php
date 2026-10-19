<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#111}
    .kop{text-align:center;border-bottom:3px double #333;padding-bottom:8px;margin-bottom:12px}
    .kop h1{margin:0;font-size:16px;text-transform:uppercase}.kop p{margin:2px 0;font-size:10px}
    h2{text-align:center;font-size:13px;margin:8px 0 2px}.meta{text-align:center;margin-bottom:12px;font-size:10px}
    .summary{width:100%;border-collapse:collapse;margin:10px 0 14px}.summary td{border:1px solid #444;padding:7px;text-align:center;width:25%}
    .summary strong{display:block;font-size:14px;margin-top:3px}
    table.data{width:100%;border-collapse:collapse}table.data th,table.data td{border:1px solid #444;padding:5px 6px}
    table.data th{background:#e9e4f7}td.c,th.c{text-align:center}.note{margin-top:10px;font-size:9px}.foot{margin-top:14px;font-size:9px;text-align:right}
</style>
</head>
<body>
@php
    $nama = $tipe === 'siswa' ? $person->nama : ($person->user?->nama ?? '(tanpa nama)');
    $kode = $tipe === 'siswa' ? $person->nis : ($person->nuptk ?: 'Tanpa NUPTK');
    $kelas = $tipe === 'siswa' ? $person->kelas?->nama : null;
@endphp
<div class="kop"><h1>{{ $sekolah }}</h1><p>{{ $alamat }}</p></div>
<h2>Laporan Monitoring Absensi Individu</h2>
<div class="meta">
    <strong>{{ $nama }}</strong><br>
    {{ $tipe === 'siswa' ? 'NIS' : 'NUPTK' }}: {{ $kode }}@if ($kelas) · Kelas {{ $kelas }}@endif<br>
    Periode: {{ $periodeLabel }} ({{ $dari->format('d/m/Y') }} s.d. {{ $sampai->format('d/m/Y') }})
</div>
<table class="summary"><tr>
    <td>Persentase hadir<strong>{{ $statistik['hadir_percent'] !== null ? $statistik['hadir_percent'].'%' : '—' }}</strong></td>
    <td>Hari tercatat<strong>{{ $statistik['hari_tercatat'] }}</strong></td>
    <td>Total absensi<strong>{{ $statistik['total'] }}</strong></td>
    <td>Hadir<strong>{{ $statistik['status']['hadir'] }}</strong></td>
</tr></table>
<h2>Riwayat Ketidakhadiran</h2>
<table class="data">
    <thead><tr><th>Tanggal</th><th>Hari</th><th>Status</th><th>Keterangan</th></tr></thead>
    <tbody>
    @forelse ($statistik['ketidakhadiran'] as $baris)
        <tr><td>{{ $baris['tanggal'] }}</td><td>{{ $baris['hari'] }}</td><td>{{ ucfirst($baris['status']) }}</td><td>{{ $baris['alasan'] ?: 'Tidak ada keterangan tercatat.' }}</td></tr>
    @empty
        <tr><td colspan="4">Tidak ada ketidakhadiran tervalidasi pada periode ini.</td></tr>
    @endforelse
    </tbody>
</table>
<p class="note">Riwayat ini hanya menampilkan sakit, izin, dan alpa yang sudah tervalidasi. Hari tanpa catatan absensi tidak dianggap alpa. Sabtu/Minggu dikecualikan; libur khusus sekolah belum tercatat di kalender aplikasi.</p>
<div class="foot">Dicetak: {{ $dicetak }}</div>
</body>
</html>

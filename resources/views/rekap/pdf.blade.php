<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#111}
    .kop{text-align:center;border-bottom:3px double #333;padding-bottom:8px;margin-bottom:12px}
    .kop h1{margin:0;font-size:16px;text-transform:uppercase} .kop p{margin:2px 0;font-size:10px}
    h2{text-align:center;font-size:13px;margin:8px 0 2px}
    .meta{text-align:center;margin-bottom:10px;font-size:10px}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #444;padding:4px 6px} th{background:#e9e4f7}
    td.c,th.c{text-align:center}
    .foot{margin-top:14px;font-size:10px;text-align:right}
</style>
</head>
<body>
<div class="kop">
    <h1>{{ $sekolah }}</h1>
    <p>{{ $alamat }}</p>
</div>
<h2>{{ $f['judul'] }}</h2>
<div class="meta">
    Periode: {{ $f['periode'] }}
    @if ($f['label_kelas']) · Kelas: {{ $f['label_kelas'] }}@endif
    · Sumber: data tervalidasi
</div>
<table>
    <thead>
        <tr><th class="c" width="28">No</th><th>{{ $f['tipe'] === 'guru' ? 'NUPTK' : 'NIS' }}</th><th>Nama</th>
            <th class="c">Hadir</th><th class="c">Sakit</th><th class="c">Izin</th><th class="c">Alpa</th></tr>
    </thead>
    <tbody>
    @forelse ($rows as $r)
        <tr>
            <td class="c">{{ $loop->iteration }}</td><td>{{ $r['kode'] }}</td><td>{{ $r['nama'] }}</td>
            <td class="c">{{ $r['hadir'] }}</td><td class="c">{{ $r['sakit'] }}</td>
            <td class="c">{{ $r['izin'] }}</td><td class="c">{{ $r['alpa'] }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="c">Tidak ada data.</td></tr>
    @endforelse
    </tbody>
</table>
<div class="foot">Dicetak: {{ $dicetak }}</div>
</body>
</html>

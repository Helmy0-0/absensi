@extends('layouts.app')
@section('judul', 'Rekap Absensi')
@section('isi')
<div class="card" x-data="{ tipe: '{{ $f['tipe'] }}' }">
    <h2>{{ $f['judul'] }}</h2>
    <p class="mut sm" style="margin-top:0">Hanya data <strong>tervalidasi</strong>. Sabtu/Minggu dan laporan kelas yang belum diisi tidak dihitung.</p>

    <form method="GET" action="{{ route($rute['index']) }}" class="filters">
        @unless ($adalahGuru)
        <div>
            <label>Tipe laporan</label>
            <select name="tipe" x-model="tipe">
                <option value="siswa">Siswa</option>
                <option value="guru">Guru</option>
            </select>
        </div>
        @endunless
        <div><label>Dari bulan</label><input type="month" name="dari" value="{{ $f['dari']->format('Y-m') }}"></div>
        <div><label>Sampai bulan</label><input type="month" name="sampai" value="{{ $f['sampai']->format('Y-m') }}"></div>
        <div x-show="tipe === 'siswa'">
            <label>Kelas</label>
            <select name="kelas_id">
                @unless ($adalahGuru)<option value="">Semua kelas</option>@endunless
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}" @selected($f['kelas_id'] == $k->id)>{{ $k->nama }} ({{ $k->tahunAjaran?->nama }})</option>
                @endforeach
            </select>
        </div>
        <div class="fix flex">
            <button class="btn">Tampilkan</button>
            <button class="btn sec" formaction="{{ route($rute['pdf']) }}" formtarget="_blank">PDF</button>
            <button class="btn sec" formaction="{{ route($rute['csv']) }}">CSV</button>
        </div>
    </form>

    @if ($adalahGuru && $kelasList->isEmpty())
        <div class="alert alert-warn">Anda belum terdaftar mengampu kelas manapun.</div>
    @endif

    <div class="mb sm"><strong>Periode:</strong> {{ $f['periode'] }}@if ($f['label_kelas']) · <strong>Kelas:</strong> {{ $f['label_kelas'] }}@endif</div>

    <div class="tw">
    <table class="tbl">
        <thead>
            <tr><th>No</th><th>{{ $f['tipe'] === 'guru' ? 'NUPTK' : 'NIS' }}</th><th>Nama</th>
                <th class="num">Hadir</th><th class="num">Sakit</th><th class="num">Izin</th><th class="num">Alpa</th></tr>
        </thead>
        <tbody>
        @forelse ($rows as $r)
            <tr>
                <td data-label="No">{{ $loop->iteration }}</td>
                <td data-label="{{ $f['tipe'] === 'guru' ? 'NUPTK' : 'NIS' }}">{{ $r['kode'] }}</td>
                <td data-label="Nama">{{ $r['nama'] }}</td>
                <td data-label="Hadir" class="num">{{ $r['hadir'] }}</td>
                <td data-label="Sakit" class="num">{{ $r['sakit'] }}</td>
                <td data-label="Izin" class="num">{{ $r['izin'] }}</td>
                <td data-label="Alpa" class="num">{{ $r['alpa'] }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">Tidak ada data pada filter ini.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection

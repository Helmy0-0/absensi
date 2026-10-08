@extends('layouts.app')
@section('judul', 'Absen Siswa')
@section('isi')

@if ($ditolak->isNotEmpty())
    <div class="alert alert-err">
        <strong>Laporan ditolak, perlu diperbaiki:</strong>
        <ul>
            @foreach ($ditolak as $d)
                <li>Kelas {{ $d->kelas?->nama }} ({{ $d->tanggal->format('d/m/Y') }}) — <em>{{ $d->catatan_validasi }}</em>
                    · <a href="{{ route('guru.absen-siswa', ['kelas_id' => $d->kelas_id, 'tanggal' => $d->tanggal->toDateString()]) }}">Perbaiki</a></li>
            @endforeach
        </ul>
    </div>
@endif

@if (! $kelas)
    <div class="alert alert-warn">Anda belum terdaftar mengampu kelas manapun pada tahun ajaran aktif. Hubungi kesiswaan/admin.</div>
@else
<div class="card">
    <form method="GET" class="filters">
        <div>
            <label>Kelas yang diampu</label>
            <select name="kelas_id" onchange="this.form.submit()">
                @foreach ($kelasList as $k)<option value="{{ $k->id }}" @selected($kelas->id === $k->id)>{{ $k->nama }}</option>@endforeach
                @unless ($kelasList->contains('id', $kelas->id))<option value="{{ $kelas->id }}" selected>{{ $kelas->nama }}</option>@endunless
            </select>
        </div>
        <div class="fix mut"><strong>{{ \App\Support\Kalender::tanggalPanjang($tanggal) }}</strong></div>
    </form>

    @switch($mode)
        @case('libur')
            <div class="alert alert-warn">Hari ini bukan hari efektif (Sabtu/Minggu). Form absensi dinonaktifkan.</div>
            @break

        @case('readonly')
            <div class="alert alert-ok">
                Sudah diabsen oleh <strong>{{ $laporan->dilaporkan_oleh === auth()->id() ? 'Anda' : $laporan->pelapor?->nama }}</strong>
                @if ($laporan->waktu_laporan) pukul {{ $laporan->waktu_laporan->format('H:i') }}@endif
                · <x-badge :status="$laporan->status_validasi" />
            </div>
            @include('partials.tabel-siswa-baca', ['rows' => $rows])
            @break

        @case('form')
            @if ($laporan?->status_validasi === 'ditolak')
                <div class="alert alert-err"><strong>Laporan ditolak.</strong> Alasan: {{ $laporan->catatan_validasi }}. Perbaiki lalu kirim ulang.</div>
            @else
                <div class="alert alert-ok">Kelas ini belum diabsen. Semua siswa default <strong>Hadir</strong>; ubah hanya yang tidak hadir.</div>
            @endif

            <form method="POST" action="{{ route('guru.absen-siswa.store') }}"
                  onsubmit="return confirm('Kirim absensi kelas ini? Data akan terkunci dan masuk antrean validasi.')">
                @csrf
                <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
                <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">
                @include('partials.tabel-siswa-form', ['rows' => $rows])
                <div class="mt"><button class="btn">Kirim</button></div>
            </form>
            @break

        @default
            <div class="alert alert-warn">Kelas ini belum memiliki siswa aktif.</div>
    @endswitch
</div>
@endif
@endsection

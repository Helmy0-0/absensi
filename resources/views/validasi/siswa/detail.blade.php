@extends('layouts.app')
@section('judul', match ($mode) { 'manual' => 'Input Manual Laporan Kelas', 'koreksi' => 'Koreksi Laporan Kelas', default => 'Detail Laporan Kelas' })
@section('isi')
<div x-data="{ tolak: false }">
<div class="card">
    <div class="flex between mb">
        <div>
            <h2 style="margin:0">Kelas {{ $kelas->nama }}</h2>
            <span class="mut sm">{{ \App\Support\Kalender::tanggalPanjang($tanggal) }}</span>
        </div>
        <a class="btn sec" href="{{ route('validasi.siswa', ['tanggal' => $tanggal->toDateString()]) }}">← Kembali</a>
    </div>

    @if ($mode !== 'manual' && $laporan)
        <div class="mb sm">
            Status: <x-badge :status="$laporan->status_validasi" />
            · Dilaporkan oleh <strong>{{ $laporan->pelapor?->nama ?? '-' }}</strong>
            @if ($laporan->waktu_laporan) pukul {{ $laporan->waktu_laporan->format('H:i') }}@endif
            @if ($laporan->diinput_manual)<span class="badge badge-purple">Input manual</span>@endif
            @if ($laporan->validator) · Divalidasi oleh {{ $laporan->validator->nama }}@endif
            @if ($laporan->catatan_validasi)<div class="err">Alasan penolakan: {{ $laporan->catatan_validasi }}</div>@endif
            @if ($laporan->catatan_koreksi)<div class="mut">Koreksi oleh {{ $laporan->korektor?->nama }}: {{ $laporan->catatan_koreksi }}</div>@endif
        </div>
    @endif

    @if ($mode === 'manual')
        <div class="alert alert-warn">Laporan ini diisi manual oleh Anda dan langsung berstatus <strong>tervalidasi</strong>. Semua siswa default Hadir.</div>
        <form method="POST" action="{{ route('validasi.siswa.input-manual.simpan') }}">
            @csrf
            <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
            <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">
            @include('partials.tabel-siswa-form', ['rows' => $rows])
            <div class="mt"><button class="btn">Simpan &amp; validasi</button></div>
        </form>

    @elseif ($mode === 'koreksi')
        <form method="POST" action="{{ route('validasi.siswa.koreksi', $laporan) }}">
            @csrf @method('PUT')
            @include('partials.tabel-siswa-form', ['rows' => $rows])
            <div class="fg mt"><label>Catatan koreksi (wajib, min. 5 karakter)</label><textarea name="catatan_koreksi" rows="2" minlength="5" required>{{ old('catatan_koreksi') }}</textarea></div>
            <button class="btn">Simpan koreksi</button>
            <a class="btn sec" href="{{ route('validasi.siswa.show', $laporan) }}">Batal</a>
        </form>

    @else
        @include('partials.tabel-siswa-baca', ['rows' => $rows])
        <div class="flex mt">
            @if ($laporan->status_validasi === 'pending')
                <form method="POST" action="{{ route('validasi.siswa.setujui', $laporan) }}">@csrf <button class="btn ok">Validasi</button></form>
                <button type="button" class="btn bad" @click="tolak = true">Tolak</button>
            @endif
            <a class="btn sec" href="{{ route('validasi.siswa.show', ['absensiKelas' => $laporan, 'edit' => 1]) }}">Koreksi</a>
        </div>

        @if ($laporan->status_validasi === 'pending')
        <div class="modal-bg" x-show="tolak" x-cloak @keydown.escape.window="tolak = false">
            <div class="modal">
                <h2>Tolak laporan kelas {{ $kelas->nama }}</h2>
                <form method="POST" action="{{ route('validasi.siswa.tolak', $laporan) }}">
                    @csrf
                    <div class="fg"><label>Alasan penolakan (min. 5 karakter)</label><textarea name="catatan_validasi" rows="3" minlength="5" required></textarea></div>
                    <button class="btn bad">Tolak</button>
                    <button type="button" class="btn sec" @click="tolak = false">Batal</button>
                </form>
            </div>
        </div>
        @endif
    @endif
</div>
</div>
@endsection

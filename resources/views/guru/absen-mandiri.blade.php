@extends('layouts.app')
@section('judul', 'Absen Mandiri')
@section('isi')
@php
    $target = $perbaikan;                 // data ditolak yang sedang diperbaiki
    $bolehForm = $target || (! $libur && ! $absen);
    $statusAwal = old('status', $target?->status ?? 'hadir');
@endphp

<div class="card" style="max-width:640px" x-data="{ status: '{{ $statusAwal }}', jam: '' }"
     x-init="const t = () => jam = new Date().toLocaleTimeString('id-ID', {timeZone:'Asia/Jakarta'}); t(); setInterval(t, 1000)">
    <h2>{{ \App\Support\Kalender::tanggalPanjang($target?->tanggal ?? $hariIni) }}</h2>
    <div class="mut mb">Jam sekarang: <strong x-text="jam"></strong> WIB</div>

    @if ($libur && ! $target)
        <div class="alert alert-warn">Hari ini bukan hari efektif (Sabtu/Minggu). Absen mandiri dinonaktifkan.</div>
    @endif

    @if ($target)
        <div class="alert alert-err">
            <strong>Absen tanggal {{ $target->tanggal->format('d/m/Y') }} ditolak.</strong><br>
            Alasan: {{ $target->catatan_validasi }}<br>
            Silakan perbaiki dan kirim ulang.
        </div>
    @endif

    @if ($absen && ! $target)
        <div class="alert alert-ok">
            Anda sudah absen hari ini: <x-badge :status="$absen->status" />
            · Validasi: <x-badge :status="$absen->status_validasi" />
            <div class="sm">Dikirim pukul {{ $absen->waktu_checkin?->format('H:i') }}. Data terkunci dan menunggu validasi kesiswaan.</div>
        </div>
    @endif

    @if ($bolehForm)
        <form method="POST" enctype="multipart/form-data"
              action="{{ $target ? route('guru.absen-mandiri.update', $target) : route('guru.absen-mandiri.store') }}">
            @csrf
            @if ($target) @method('PUT') @endif

            <div class="fg">
                <label>Status kehadiran</label>
                <div class="st">
                    @foreach (['hadir' => ['h', 'Hadir'], 'izin' => ['i', 'Izin'], 'sakit' => ['s', 'Sakit']] as $v => [$c, $l])
                        <label><input type="radio" name="status" value="{{ $v }}" x-model="status" @checked($statusAwal === $v)><span class="{{ $c }}">{{ $l }}</span></label>
                    @endforeach
                </div>
            </div>

            <div x-show="status !== 'hadir'" x-cloak>
                <div class="fg"><label>Alasan</label><textarea name="alasan" rows="3">{{ old('alasan', $target?->alasan) }}</textarea></div>
                <div class="fg">
                    <label>Bukti (surat tugas / surat dokter — jpg, png, pdf; maks. 2 MB)</label>
                    <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf">
                    @if ($target?->bukti_path)<div class="mut sm">Sudah ada bukti terunggah; unggah file baru untuk menggantinya.</div>@endif
                </div>
            </div>

            <button class="btn">{{ $target ? 'Kirim ulang perbaikan' : 'Kirim absen' }}</button>
        </form>
    @endif
</div>

@if ($ditolakLain->isNotEmpty())
<div class="card" style="max-width:640px">
    <h2>Absen lain yang perlu diperbaiki</h2>
    <ul>
        @foreach ($ditolakLain as $d)
            <li>{{ $d->tanggal->format('d/m/Y') }} — <em>{{ $d->catatan_validasi }}</em>
                · <a href="{{ route('guru.absen-mandiri.edit', $d) }}">Perbaiki</a></li>
        @endforeach
    </ul>
</div>
@endif
@endsection

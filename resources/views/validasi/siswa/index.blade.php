@extends('layouts.app')
@section('judul', 'Validasi Absen Siswa')
@section('isi')
<div x-data="{ tolak: null }">
<div class="card">
    <h2>Laporan Kelas — {{ \App\Support\Kalender::tanggalPanjang($tanggal) }}</h2>

    <form method="GET" class="filters validation-filters">
        <div class="fix"><label>Tanggal</label><input type="date" name="tanggal" value="{{ $tanggal->toDateString() }}"></div>
        <div><label>Cari kelas</label><input type="search" name="q" value="{{ $cari }}" placeholder="Ketik nama kelas..."></div>
        <div><label>Tampilkan</label><select name="filter">
            <option value="semua" @selected($filter === 'semua')>Semua status</option>
            <option value="pending" @selected($filter === 'pending')>Menunggu validasi</option>
            <option value="belum_laporan" @selected($filter === 'belum_laporan')>Belum ada laporan</option>
        </select></div>
        <div class="fix"><button class="btn">Cari</button> <a class="btn sec" href="{{ route('validasi.siswa') }}">Reset</a></div>
    </form>

    @if ($libur)
        <div class="alert alert-warn">Tanggal ini hari libur (Sabtu/Minggu). Tidak ada laporan yang dibuat atau dihitung.</div>
    @else
        <div class="validation-summary">
            <a href="{{ route('validasi.siswa', ['tanggal' => $tanggal->toDateString(), 'filter' => 'pending']) }}" class="validation-count pending"><span>Menunggu validasi</span><strong>{{ $jumlahMenunggu }}</strong></a>
            <a href="{{ route('validasi.siswa', ['tanggal' => $tanggal->toDateString(), 'filter' => 'belum_laporan']) }}" class="validation-count missing"><span>Belum ada laporan</span><strong>{{ $jumlahBelumLaporan }}</strong></a>
            <div class="validation-note">Sistem tidak otomatis mengalpakan siswa. Kelas tanpa laporan harus diisi lewat <strong>Input Manual</strong>.</div>
        </div>
        <div class="flex between mb">
            <form method="POST" action="{{ route('validasi.siswa.setujui-semua') }}" onsubmit="return confirm('Validasi semua laporan kelas yang masih menunggu pada tanggal ini?')">
                @csrf <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">
                <button class="btn ok" @disabled(! $adaPending)>Validasi semua kelas</button>
            </form>
        </div>

        <div class="tw">
        <table class="tbl">
            <thead><tr><th>Waktu Laporan</th><th>Kelas</th><th>Dilaporkan Oleh</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                @php $l = $r['laporan']; $k = $r['kelas']; $kosong = ! $l || $l->status_validasi === 'tidak_diabsen'; @endphp
                <tr class="{{ $kosong ? 'warn-row' : '' }}">
                    <td data-label="Waktu">{{ $l?->waktu_laporan?->format('H:i') ?? '-' }}</td>
                    <td data-label="Kelas"><strong>{{ $k->nama }}</strong></td>
                    <td data-label="Pelapor">
                        {{ $l?->pelapor?->nama ?? '-' }}
                        @if ($l?->diinput_manual)<span class="badge badge-purple">Manual</span>@endif
                    </td>
                    <td data-label="Status">
                        @if ($l)<x-badge :status="$l->status_validasi" />@else<x-badge status="belum" />@endif
                        @if ($l?->status_validasi === 'ditolak' && $l->catatan_validasi)
                            <div class="err">{{ $l->catatan_validasi }}</div>
                        @endif
                    </td>
                    <td class="act">
                        @if ($kosong)
                            <a class="btn sm" href="{{ route('validasi.siswa.input-manual', ['kelas_id' => $k->id, 'tanggal' => $tanggal->toDateString()]) }}">Input Manual</a>
                        @else
                            <a class="btn sm sec" href="{{ route('validasi.siswa.show', $l) }}">Detail</a>
                            @if ($l->status_validasi === 'pending')
                                <form method="POST" action="{{ route('validasi.siswa.setujui', $l) }}">@csrf <button class="btn sm ok">Validasi</button></form>
                                <button type="button" class="btn sm bad" @click="tolak = { id: {{ $l->id }}, nama: @js($k->nama) }">Tolak</button>
                            @endif
                            <a class="btn sm sec" href="{{ route('validasi.siswa.show', ['absensiKelas' => $l, 'edit' => 1]) }}">Koreksi</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Tidak ada kelas.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    @endif
</div>

<div class="modal-bg" x-show="tolak" x-cloak @keydown.escape.window="tolak = null">
    <div class="modal">
        <h2>Tolak laporan kelas <span x-text="tolak?.nama"></span></h2>
        <template x-if="tolak">
            <form method="POST" :action="'{{ url('validasi/siswa') }}/' + tolak.id + '/tolak'">
                @csrf
                <div class="fg"><label>Alasan penolakan (min. 5 karakter)</label><textarea name="catatan_validasi" rows="3" minlength="5" required></textarea></div>
                <button class="btn bad">Tolak</button>
                <button type="button" class="btn sec" @click="tolak = null">Batal</button>
            </form>
        </template>
    </div>
</div>
</div>
@endsection

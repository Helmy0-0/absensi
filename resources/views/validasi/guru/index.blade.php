@extends('layouts.app')
@section('judul', 'Validasi Absen Guru')
@section('isi')
<div x-data="{ tolak: null, koreksi: null }">
<div class="card">
    <h2>Absen Guru — {{ \App\Support\Kalender::tanggalPanjang($tanggal) }}</h2>

    <form method="GET" class="filters validation-filters">
        <div class="fix"><label>Tanggal</label><input type="date" name="tanggal" value="{{ $tanggal->toDateString() }}"></div>
        <div><label>Cari guru</label><input type="search" name="q" value="{{ $cari }}" placeholder="Ketik nama guru..."></div>
        <div><label>Tampilkan</label><select name="filter">
            <option value="semua" @selected($filter === 'semua')>Semua status</option>
            <option value="pending" @selected($filter === 'pending')>Menunggu validasi</option>
            <option value="belum_absen" @selected($filter === 'belum_absen')>Belum absen</option>
        </select></div>
        <div class="fix"><button class="btn">Cari</button> <a class="btn sec" href="{{ route('validasi.guru') }}">Reset</a></div>
    </form>

    @if ($libur)
        <div class="alert alert-warn">Tanggal ini hari libur (Sabtu/Minggu). Tidak ada absensi yang dibuat atau dihitung.</div>
    @else
        <div class="validation-summary">
            <a href="{{ route('validasi.guru', ['tanggal' => $tanggal->toDateString(), 'filter' => 'pending']) }}" class="validation-count pending"><span>Menunggu validasi</span><strong>{{ $jumlahMenunggu }}</strong></a>
            <a href="{{ route('validasi.guru', ['tanggal' => $tanggal->toDateString(), 'filter' => 'belum_absen']) }}" class="validation-count missing"><span>Belum absen</span><strong>{{ $jumlahBelumAbsen }}</strong></a>
            <div class="validation-note">Guru yang belum check-in menjadi <strong>alpa otomatis</strong> pada pukul {{ config('absensi.jam_alpa_otomatis') }}.</div>
        </div>
        <div class="flex between mb">
            <form method="POST" action="{{ route('validasi.guru.setujui-semua') }}" onsubmit="return confirm('Setujui semua absen guru yang masih menunggu pada tanggal ini?')">
                @csrf <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">
                <button class="btn ok" @disabled(! $adaPending)>Setujui semua pending</button>
            </form>
        </div>

        <div class="tw">
        <table class="tbl">
            <thead><tr><th>No</th><th>Nama Guru</th><th>Check-in</th><th>Status</th><th>Bukti / Keterangan</th><th>Validasi</th><th></th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                @php $a = $r['absen']; $g = $r['guru']; @endphp
                <tr>
                    <td data-label="No">{{ $loop->iteration }}</td>
                    <td data-label="Guru">{{ $g->user?->nama }}</td>
                    <td data-label="Check-in">{{ $a?->waktu_checkin?->format('H:i') ?? '-' }}</td>
                    <td data-label="Status">
                        @if ($a)<x-badge :status="$a->status" />@else<x-badge status="belum" />@endif
                        @if ($a?->dibuat_sistem)<span class="badge badge-gray">Otomatis</span>@endif
                    </td>
                    <td data-label="Keterangan">
                        @if ($a?->alasan){{ $a->alasan }}@endif
                        @if ($a?->bukti_path)
                            <a href="{{ route('bukti.guru', $a) }}" target="_blank" rel="noopener">[lihat bukti]</a>
                        @endif
                        @if ($a?->status_validasi === 'ditolak' && $a->catatan_validasi)
                            <div class="err">Ditolak: {{ $a->catatan_validasi }}</div>
                        @endif
                        @if ($a?->catatan_koreksi)
                            <div class="mut sm">Koreksi: {{ $a->catatan_koreksi }}</div>
                        @endif
                        @if (! $a?->alasan && ! $a?->bukti_path && ! $a?->catatan_koreksi && $a?->status_validasi !== 'ditolak')-@endif
                    </td>
                    <td data-label="Validasi">@if ($a)<x-badge :status="$a->status_validasi" />@else <span class="mut">-</span>@endif</td>
                    <td class="act">
                        @if ($a && $a->status_validasi === 'pending')
                            <form method="POST" action="{{ route('validasi.guru.setujui', $a) }}">@csrf <button class="btn sm ok">Setujui</button></form>
                            <button type="button" class="btn sm bad" @click="tolak = { id: {{ $a->id }}, nama: @js($g->user?->nama) }">Tolak</button>
                        @endif
                        <button type="button" class="btn sm sec"
                            @click="koreksi = { guru_id: {{ $g->id }}, nama: @js($g->user?->nama), status: '{{ $a?->status ?? 'hadir' }}', alasan: @js($a?->alasan ?? '') }">
                            {{ $a ? 'Koreksi' : 'Isi manual' }}
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Tidak ada guru aktif.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    @endif
</div>

{{-- Modal tolak --}}
<div class="modal-bg" x-show="tolak" x-cloak @keydown.escape.window="tolak = null">
    <div class="modal">
        <h2>Tolak absen <span x-text="tolak?.nama"></span></h2>
        <template x-if="tolak">
            <form method="POST" :action="'{{ url('validasi/guru') }}/' + tolak.id + '/tolak'">
                @csrf
                <div class="fg"><label>Alasan penolakan (min. 5 karakter)</label><textarea name="catatan_validasi" rows="3" minlength="5" required></textarea></div>
                <button class="btn bad">Tolak</button>
                <button type="button" class="btn sec" @click="tolak = null">Batal</button>
            </form>
        </template>
    </div>
</div>

{{-- Modal koreksi --}}
<div class="modal-bg" x-show="koreksi" x-cloak @keydown.escape.window="koreksi = null">
    <div class="modal">
        <h2>Koreksi absen <span x-text="koreksi?.nama"></span></h2>
        <p class="mut sm" style="margin-top:0">Tanggal {{ $tanggal->format('d/m/Y') }}. Hasil koreksi langsung berstatus tervalidasi.</p>
        <template x-if="koreksi">
            <form method="POST" action="{{ route('validasi.guru.koreksi') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="guru_id" :value="koreksi.guru_id">
                <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">
                <div class="fg">
                    <label>Status</label>
                    <select name="status" x-model="koreksi.status">
                        <option value="hadir">Hadir</option><option value="izin">Izin</option>
                        <option value="sakit">Sakit</option><option value="alpa">Alpa</option>
                    </select>
                </div>
                <div class="fg" x-show="koreksi.status === 'izin' || koreksi.status === 'sakit'">
                    <label>Alasan</label><textarea name="alasan" rows="2" x-model="koreksi.alasan"></textarea>
                </div>
                <div class="fg" x-show="koreksi.status === 'izin' || koreksi.status === 'sakit'">
                    <label>Bukti (opsional, jpg/png/pdf maks. 2 MB)</label><input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf">
                </div>
                <div class="fg"><label>Catatan koreksi (wajib)</label><textarea name="catatan_koreksi" rows="2" minlength="5" required placeholder="mis. surat dokter menyusul"></textarea></div>
                <button class="btn">Simpan koreksi</button>
                <button type="button" class="btn sec" @click="koreksi = null">Batal</button>
            </form>
        </template>
    </div>
</div>
</div>
@endsection

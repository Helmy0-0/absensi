@extends('layouts.app')
@section('judul', 'Tahun Ajaran')
@section('isi')
<div x-data="{ edit: null }">
    <div class="card">
        <div class="flex between mb">
            <h2 style="margin:0">Daftar Tahun Ajaran</h2>
            <a class="btn sec" href="{{ route('master.kelas.index') }}">← Kelola Kelas</a>
        </div>
        <p class="mut sm">Hanya satu tahun ajaran yang aktif. Tahun ajaran baru = kelas baru, sehingga relasi guru–kelas dan rekap lama tetap utuh.</p>
        <div class="tw">
        <table class="tbl">
            <thead><tr><th>Nama</th><th>Mulai</th><th>Selesai</th><th class="num">Kelas</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($tahunList as $t)
                <tr>
                    <td data-label="Nama">{{ $t->nama }}</td>
                    <td data-label="Mulai">{{ $t->tanggal_mulai->format('d/m/Y') }}</td>
                    <td data-label="Selesai">{{ $t->tanggal_selesai->format('d/m/Y') }}</td>
                    <td data-label="Kelas" class="num">{{ $t->kelas_count }}</td>
                    <td data-label="Status"><x-badge :status="$t->is_aktif ? 'aktif' : 'nonaktif'" /></td>
                    <td class="act">
                        <button type="button" class="btn sm sec"
                            @click="edit = { id: {{ $t->id }}, nama: @js($t->nama), mulai: '{{ $t->tanggal_mulai->toDateString() }}', selesai: '{{ $t->tanggal_selesai->toDateString() }}', aktif: {{ $t->is_aktif ? 'true' : 'false' }} }">Edit</button>
                        @unless ($t->is_aktif)
                        <form method="POST" action="{{ route('master.tahun-ajaran.aktifkan', $t) }}" onsubmit="return confirm('Jadikan {{ $t->nama }} tahun ajaran aktif?')">
                            @csrf @method('PATCH') <button class="btn sm ok">Aktifkan</button>
                        </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Belum ada tahun ajaran.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="card" style="max-width:560px">
        <h2>Tambah Tahun Ajaran</h2>
        <form method="POST" action="{{ route('master.tahun-ajaran.store') }}">
            @csrf
            <div class="fg"><label>Nama</label><input type="text" name="nama" placeholder="2026/2027" value="{{ old('nama') }}" required></div>
            <div class="grid g2">
                <div class="fg"><label>Tanggal mulai</label><input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required></div>
                <div class="fg"><label>Tanggal selesai</label><input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" required></div>
            </div>
            <div class="fg chk"><input type="checkbox" id="aktif_baru" name="is_aktif" value="1"><label for="aktif_baru" style="margin:0">Tetapkan sebagai tahun ajaran aktif</label></div>
            <button class="btn">Tambah</button>
        </form>
    </div>

    <div class="modal-bg" x-show="edit" x-cloak @keydown.escape.window="edit = null">
        <div class="modal" @click.outside="edit = null">
            <h2>Edit Tahun Ajaran</h2>
            <template x-if="edit">
                <form method="POST" :action="'{{ url('master/tahun-ajaran') }}/' + edit.id">
                    @csrf @method('PUT')
                    <div class="fg"><label>Nama</label><input type="text" name="nama" x-model="edit.nama" required></div>
                    <div class="grid g2">
                        <div class="fg"><label>Mulai</label><input type="date" name="tanggal_mulai" x-model="edit.mulai" required></div>
                        <div class="fg"><label>Selesai</label><input type="date" name="tanggal_selesai" x-model="edit.selesai" required></div>
                    </div>
                    <div class="fg chk"><input type="checkbox" name="is_aktif" value="1" x-model="edit.aktif" id="aktif_edit"><label for="aktif_edit" style="margin:0">Tahun ajaran aktif</label></div>
                    <button class="btn">Simpan</button>
                    <button type="button" class="btn sec" @click="edit = null">Batal</button>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection

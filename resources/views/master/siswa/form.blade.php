@extends('layouts.app')
@section('judul', $siswa ? 'Edit Siswa' : 'Tambah Siswa')
@section('isi')
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ $siswa ? route('master.siswa.update', $siswa) : route('master.siswa.store') }}">
        @csrf
        @if ($siswa) @method('PUT') @endif
        <div class="fg"><label>NIS</label><input type="text" name="nis" value="{{ old('nis', $siswa?->nis) }}" required></div>
        <div class="fg"><label>Nama</label><input type="text" name="nama" value="{{ old('nama', $siswa?->nama) }}" required></div>
        <div class="fg">
            <label>Status</label>
            <select name="status" required>
                @foreach (['aktif' => 'Aktif', 'lulus' => 'Lulus', 'pindah' => 'Pindah', 'nonaktif' => 'Nonaktif'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('status', $siswa?->status ?? 'aktif') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label>Kelas</label>
            <select name="kelas_id">
                <option value="">— Tanpa kelas —</option>
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}" @selected(old('kelas_id', $siswa?->kelas_id) == $k->id)>{{ $k->nama }}</option>
                @endforeach
            </select>
            <div class="mut sm">Memindahkan kelas tidak mengubah riwayat absensi yang sudah ada.</div>
        </div>
        <button class="btn">Simpan</button>
        <a class="btn sec" href="{{ route('master.siswa.index') }}">Batal</a>
    </form>
</div>
@endsection

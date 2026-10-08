@extends('layouts.app')
@section('judul', $relasi ? 'Edit Relasi Guru–Kelas' : 'Tambah Relasi Guru–Kelas')
@section('isi')
<div class="card" style="max-width:560px" x-data="{ peran: '{{ old('peran', $relasi?->peran ?? 'guru_mapel') }}' }">
    <form method="POST" action="{{ $relasi ? route('master.relasi.update', $relasi) : route('master.relasi.store') }}">
        @csrf
        @if ($relasi) @method('PUT') @endif
        <div class="fg">
            <label>Guru (aktif)</label>
            <select name="guru_id" required>
                <option value="">— Pilih guru —</option>
                @foreach ($guruList as $g)
                    <option value="{{ $g->id }}" @selected(old('guru_id', $relasi?->guru_id) == $g->id)>{{ $g->user?->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label>Kelas (tahun ajaran aktif)</label>
            <select name="kelas_id" required>
                <option value="">— Pilih kelas —</option>
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}" @selected(old('kelas_id', $relasi?->kelas_id) == $k->id)>{{ $k->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label>Peran penugasan</label>
            <select name="peran" x-model="peran" required>
                <option value="wali">Wali kelas</option>
                <option value="guru_mapel">Guru mapel</option>
            </select>
            <div class="mut sm">Satu kelas hanya boleh memiliki satu wali.</div>
        </div>
        <div class="fg">
            <label>Mata pelajaran <span x-show="peran === 'guru_mapel'">(wajib)</span></label>
            <input type="text" name="mata_pelajaran" value="{{ old('mata_pelajaran', $relasi?->mata_pelajaran) }}">
        </div>
        <button class="btn">Simpan</button>
        <a class="btn sec" href="{{ route('master.relasi.index') }}">Batal</a>
    </form>
</div>
@endsection

@extends('layouts.app')
@section('judul', $kelas ? 'Edit Kelas' : 'Tambah Kelas')
@section('isi')
<div class="card" style="max-width:560px">
    <form method="POST" action="{{ $kelas ? route('master.kelas.update', $kelas) : route('master.kelas.store') }}">
        @csrf
        @if ($kelas) @method('PUT') @endif
        <div class="fg"><label>Nama Kelas</label><input type="text" name="nama" value="{{ old('nama', $kelas?->nama) }}" placeholder="mis. X-1" required></div>
        <div class="grid g2">
            <div class="fg">
                <label>Tingkat</label>
                <select name="tingkat" required>
                    @foreach ([10, 11, 12] as $t)<option value="{{ $t }}" @selected(old('tingkat', $kelas?->tingkat) == $t)>{{ $t }}</option>@endforeach
                </select>
            </div>
            <div class="fg"><label>Ruangan</label><input type="text" name="ruangan" value="{{ old('ruangan', $kelas?->ruangan) }}"></div>
        </div>
        <div class="fg">
            <label>Tahun Ajaran</label>
            <select name="tahun_ajaran_id" required>
                @foreach ($tahunList as $t)
                    <option value="{{ $t->id }}" @selected(old('tahun_ajaran_id', $kelas?->tahun_ajaran_id ?? ($t->is_aktif ? $t->id : null)) == $t->id)>{{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn">Simpan</button>
        <a class="btn sec" href="{{ route('master.kelas.index') }}">Batal</a>
    </form>
</div>
@endsection

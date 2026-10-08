@extends('layouts.app')
@section('judul', 'Relasi Guru–Kelas')
@section('isi')
<div class="card">
    <div class="flex between mb">
        <h2 style="margin:0">Relasi Guru–Kelas</h2>
        <a class="btn" href="{{ route('master.relasi.create') }}">+ Tambah Relasi</a>
    </div>

    <form method="GET" class="filters">
        <div>
            <label>Kelas</label>
            <select name="kelas_id">
                <option value="">Semua kelas</option>
                @foreach ($kelasList as $k)<option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>{{ $k->nama }}</option>@endforeach
            </select>
        </div>
        <div class="fix"><button class="btn">Filter</button> <a class="btn sec" href="{{ route('master.relasi.index') }}">Reset</a></div>
    </form>

    <div class="tw">
    <table class="tbl">
        <thead><tr><th>No</th><th>Nama Guru</th><th>Kelas</th><th>Peran</th><th>Mata Pelajaran</th><th></th></tr></thead>
        <tbody>
        @forelse ($relasi as $r)
            <tr>
                <td data-label="No">{{ $relasi->firstItem() + $loop->index }}</td>
                <td data-label="Nama Guru">{{ $r->guru?->user?->nama }}</td>
                <td data-label="Kelas">{{ $r->kelas?->nama }} <span class="mut sm">({{ $r->kelas?->tahunAjaran?->nama }})</span></td>
                <td data-label="Peran"><x-badge :status="$r->peran" /></td>
                <td data-label="Mata Pelajaran">{{ $r->mata_pelajaran ?: '-' }}</td>
                <td class="act">
                    <a class="btn sm sec" href="{{ route('master.relasi.edit', $r) }}">Edit</a>
                    <form method="POST" action="{{ route('master.relasi.destroy', $r) }}" onsubmit="return confirm('Hapus relasi ini? Laporan lama tidak terpengaruh.')">
                        @csrf @method('DELETE') <button class="btn sm bad">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Belum ada relasi.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $relasi->links() }}
</div>
@endsection

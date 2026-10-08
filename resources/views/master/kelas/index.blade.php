@extends('layouts.app')
@section('judul', 'Kelola Kelas')
@section('isi')
<div class="card">
    <div class="flex between mb">
        <h2 style="margin:0">Daftar Kelas</h2>
        <div class="flex">
            <a class="btn sec" href="{{ route('master.tahun-ajaran.index') }}">Tahun Ajaran</a>
            <a class="btn" href="{{ route('master.kelas.create') }}">+ Tambah Kelas</a>
        </div>
    </div>

    <form method="GET" class="filters">
        <div><label>Nama kelas</label><input type="search" name="q" value="{{ request('q') }}"></div>
        <div>
            <label>Tingkat</label>
            <select name="tingkat">
                <option value="">Semua</option>
                @foreach ([10, 11, 12] as $t)<option value="{{ $t }}" @selected(request('tingkat') == $t)>{{ $t }}</option>@endforeach
            </select>
        </div>
        <div>
            <label>Tahun ajaran</label>
            <select name="tahun_ajaran_id">
                @foreach ($tahunList as $t)
                    <option value="{{ $t->id }}" @selected($tahunId == $t->id)>{{ $t->nama }}{{ $t->is_aktif ? ' (aktif)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="fix"><button class="btn">Filter</button> <a class="btn sec" href="{{ route('master.kelas.index') }}">Reset</a></div>
    </form>

    <div class="tw">
    <table class="tbl">
        <thead><tr><th>No</th><th>Kelas</th><th>Tingkat</th><th>Ruangan</th><th>Tahun Ajaran</th><th class="num">Siswa Aktif</th><th></th></tr></thead>
        <tbody>
        @forelse ($kelas as $k)
            <tr>
                <td data-label="No">{{ $kelas->firstItem() + $loop->index }}</td>
                <td data-label="Kelas">{{ $k->nama }}</td>
                <td data-label="Tingkat">{{ $k->tingkat }}</td>
                <td data-label="Ruangan">{{ $k->ruangan ?: '-' }}</td>
                <td data-label="Tahun Ajaran">{{ $k->tahunAjaran?->nama }}</td>
                <td data-label="Siswa Aktif" class="num">{{ $k->siswa_aktif_count }}</td>
                <td class="act">
                    <a class="btn sm sec" href="{{ route('master.kelas.show', $k) }}">Siswa</a>
                    <a class="btn sm sec" href="{{ route('master.kelas.edit', $k) }}">Edit</a>
                    <form method="POST" action="{{ route('master.kelas.destroy', $k) }}" onsubmit="return confirm('Hapus kelas {{ $k->nama }}?')">
                        @csrf @method('DELETE') <button class="btn sm bad">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $kelas->links() }}
</div>
@endsection

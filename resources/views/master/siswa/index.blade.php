@extends('layouts.app')
@section('judul', 'Kelola Siswa')
@section('isi')
@if (session('laporan_import'))
    @php $lap = session('laporan_import'); @endphp
    <div class="alert {{ count($lap['gagal']) ? 'alert-warn' : 'alert-ok' }}">
        <strong>Laporan import:</strong>
        {{ $lap['berhasil'] }} berhasil, {{ $lap['dilewati'] }} dilewati (NIS sudah ada), {{ count($lap['gagal']) }} gagal.
        @if (count($lap['gagal']))
            <ul>
                @foreach ($lap['gagal'] as $g)
                    <li>Baris {{ $g['baris'] }}: {{ $g['alasan'] }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

<div class="card" x-data="{ imp: false }">
    <div class="flex between mb">
        <h2 style="margin:0">Daftar Siswa</h2>
        <div class="flex">
            <button type="button" class="btn sec" @click="imp = !imp">Import CSV</button>
            <a class="btn" href="{{ route('master.siswa.create') }}">+ Tambah Siswa</a>
        </div>
    </div>

    <div x-show="imp" x-cloak class="card" style="background:var(--p-xl)">
        <form method="POST" action="{{ route('master.siswa.import') }}" enctype="multipart/form-data">
            @csrf
            <p class="sm" style="margin-top:0">
                Header wajib: <code>nis,nama,nama_kelas</code>.
                <code>nama_kelas</code> harus cocok dengan kelas pada tahun ajaran aktif. NIS yang sudah ada dilewati.
                Maks. 2 MB, UTF-8. <a href="{{ route('master.siswa.template') }}">Unduh template CSV</a>
            </p>
            <div class="flex"><input type="file" name="file" accept=".csv,.txt" required style="max-width:340px"><button class="btn">Unggah &amp; Proses</button></div>
        </form>
    </div>

    <form method="GET" class="filters">
        <div><label>Cari nama / NIS</label><input type="search" name="q" value="{{ request('q') }}"></div>
        <div>
            <label>Kelas / Tingkat</label>
            <select name="kelas_id">
                <option value="">Semua kelas</option>
                <optgroup label="Semua kelas per tingkat">
                    @foreach ([10 => 'X', 11 => 'XI', 12 => 'XII'] as $tingkat => $label)
                        <option value="tingkat:{{ $tingkat }}" @selected(request('kelas_id') === 'tingkat:'.$tingkat)>Semua kelas {{ $label }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Kelas tertentu">
                    @foreach ($kelasList as $k)<option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>{{ $k->nama }}</option>@endforeach
                </optgroup>
            </select>
        </div>
        <div class="fix"><button class="btn">Filter</button> <a class="btn sec" href="{{ route('master.siswa.index') }}">Reset</a></div>
    </form>

    <div class="tw">
    <table class="tbl">
        <thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($siswa as $s)
            <tr>
                <td data-label="No">{{ $siswa->firstItem() + $loop->index }}</td>
                <td data-label="NIS">{{ $s->nis }}</td>
                <td data-label="Nama">{{ $s->nama }}</td>
                <td data-label="Kelas">{{ $s->kelas?->nama ?? '-' }}</td>
                <td data-label="Status"><x-badge :status="$s->status" /></td>
                <td class="act">
                    <a class="btn sm sec" href="{{ route('master.siswa.edit', $s) }}">Edit</a>
                    <form method="POST" action="{{ route('master.siswa.destroy', $s) }}" onsubmit="return confirm('Hapus siswa {{ $s->nama }}? Riwayat absensi tetap tersimpan.')">
                        @csrf @method('DELETE') <button class="btn sm bad">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    {{ $siswa->links() }}
</div>
@endsection

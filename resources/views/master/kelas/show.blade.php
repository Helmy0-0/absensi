@extends('layouts.app')
@section('judul', 'Siswa Kelas '.$kelas->nama)
@section('isi')
<div class="card">
    <div class="flex between mb">
        <div>
            <h2 style="margin:0">Kelas {{ $kelas->nama }}</h2>
            <span class="mut sm">Tingkat {{ $kelas->tingkat }} · Tahun ajaran {{ $kelas->tahunAjaran?->nama }} · Ruang {{ $kelas->ruangan ?: '-' }}</span>
        </div>
        <a class="btn sec" href="{{ route('master.kelas.index') }}">← Kembali</a>
    </div>
    <div class="tw">
    <table class="tbl">
        <thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($siswa as $s)
            <tr>
                <td data-label="No">{{ $loop->iteration }}</td><td data-label="NIS">{{ $s->nis }}</td>
                <td data-label="Nama">{{ $s->nama }}</td>
                <td data-label="Status"><x-badge :status="$s->status" /></td>
            </tr>
        @empty
            <tr><td colspan="4" class="empty">Belum ada siswa.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection

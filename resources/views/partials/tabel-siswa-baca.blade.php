{{-- Tabel baca-saja status siswa. Variabel: $rows (siswa, status, keterangan). --}}
<div class="tw">
<table class="tbl">
    <thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Status</th><th>Keterangan</th></tr></thead>
    <tbody>
    @forelse ($rows as $r)
        <tr>
            <td data-label="No">{{ $loop->iteration }}</td>
            <td data-label="NIS">{{ $r['siswa']?->nis }}</td>
            <td data-label="Nama">{{ $r['siswa']?->nama }}</td>
            <td data-label="Status"><x-badge :status="$r['status']" /></td>
            <td data-label="Keterangan">{{ $r['keterangan'] ?: '-' }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="empty">Tidak ada data siswa.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

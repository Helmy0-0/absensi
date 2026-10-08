{{-- Tabel editable status siswa. Variabel: $rows (siswa, status, keterangan). --}}
<div x-data="{ q: '' }">
    <div class="fg"><input type="search" x-model="q" placeholder="Cari nama siswa..."></div>
    <div class="tw">
    <table class="tbl">
        <thead><tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Status</th><th>Keterangan (wajib untuk sakit/izin)</th></tr></thead>
        <tbody>
        @foreach ($rows as $r)
            @php $id = $r['siswa']->id; $ket = old("siswa.$id.keterangan", $r['keterangan']); @endphp
            <tr x-data="{ s: '{{ old("siswa.$id.status", $r['status']) }}' }"
                data-nama="{{ mb_strtolower($r['siswa']->nama) }}"
                x-show="$el.dataset.nama.includes(q.toLowerCase())">
                <td data-label="No">{{ $loop->iteration }}</td>
                <td data-label="NIS">{{ $r['siswa']->nis }}</td>
                <td data-label="Nama">{{ $r['siswa']->nama }}</td>
                <td data-label="Status"><x-status-radio :id="$id" :status="$r['status']" /></td>
                <td data-label="Keterangan">
                    <input type="text" name="siswa[{{ $id }}][keterangan]" value="{{ $ket }}" maxlength="255"
                           :required="s === 'sakit' || s === 'izin'" :disabled="s === 'hadir'"
                           placeholder="mis. demam / acara keluarga">
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

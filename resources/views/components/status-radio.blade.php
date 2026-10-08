{{-- Butuh scope Alpine induk dengan variabel `s` (status baris ini). --}}
@props(['id', 'status' => 'hadir'])
@php $status = old("siswa.$id.status", $status); @endphp
<div class="st">
    @foreach (['hadir' => ['h', 'Hadir'], 'sakit' => ['s', 'Sakit'], 'izin' => ['i', 'Izin'], 'alpa' => ['a', 'Alpa']] as $v => [$c, $l])
        <label>
            <input type="radio" name="siswa[{{ $id }}][status]" value="{{ $v }}" x-model="s" @checked($status === $v)>
            <span class="{{ $c }}">{{ $l }}</span>
        </label>
    @endforeach
</div>

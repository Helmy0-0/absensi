@props(['label', 'value', 'hint' => null])
<div class="stat">
    <div class="l">{{ $label }}</div>
    <div class="v">{{ $value }}</div>
    @if ($hint)<div class="h">{{ $hint }}</div>@endif
</div>

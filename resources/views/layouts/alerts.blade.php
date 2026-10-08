@if (session('success'))
    <div class="alert alert-ok">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="alert alert-err">{{ session('error') }}</div>
@endif
@if ($errors->any() && ! $errors->has('login'))
    <div class="alert alert-err">
        Periksa kembali isian Anda:
        <ul>
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

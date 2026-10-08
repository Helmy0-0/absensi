<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /** Login dengan username ATAU email; akun nonaktif / terhapus tidak bisa masuk. */
    public function authenticate(): void
    {
        $this->pastikanTidakTerlaluBanyakPercobaan();

        $login = trim($this->input('login'));
        $kolom = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $berhasil = Auth::attempt(
            [$kolom => $login, 'password' => $this->input('password'), 'is_active' => true],
            $this->boolean('ingat')
        );

        if (! $berhasil) {
            RateLimiter::hit($this->kunciThrottle());

            throw ValidationException::withMessages([
                'login' => 'Username/email atau password salah, atau akun dinonaktifkan.',
            ]);
        }

        RateLimiter::clear($this->kunciThrottle());
    }

    private function pastikanTidakTerlaluBanyakPercobaan(): void
    {
        if (! RateLimiter::tooManyAttempts($this->kunciThrottle(), 5)) {
            return;
        }

        event(new Lockout($this));
        $detik = RateLimiter::availableIn($this->kunciThrottle());

        throw ValidationException::withMessages([
            'login' => "Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik.",
        ]);
    }

    private function kunciThrottle(): string
    {
        return Str::transliterate(Str::lower($this->input('login', '')).'|'.$this->ip());
    }
}

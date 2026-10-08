<?php

namespace App\Http\Controllers;

use App\Models\AbsensiGuru;
use Illuminate\Support\Facades\Storage;

class BuktiController extends Controller
{
    /** Bukti disimpan di storage privat; hanya pemilik, kesiswaan, dan admin yang boleh membuka. */
    public function guru(AbsensiGuru $absensiGuru)
    {
        $this->authorize('lihatBukti', $absensiGuru);

        abort_unless(
            $absensiGuru->bukti_path && Storage::disk('local')->exists($absensiGuru->bukti_path),
            404,
            'Bukti tidak ditemukan.'
        );

        return Storage::disk('local')->response($absensiGuru->bukti_path);
    }
}

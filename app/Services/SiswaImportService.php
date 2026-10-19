<?php

namespace App\Services;

use App\Exceptions\BisnisException;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\UploadedFile;

class SiswaImportService
{
    public const HEADER = ['nis', 'nama', 'nama_kelas'];

    /**
     * @return array{berhasil:int, dilewati:int, gagal:array<int, array{baris:int, alasan:string}>}
     */
    public function import(UploadedFile $file): array
    {
        $isi = file_get_contents($file->getRealPath());

        if (! mb_check_encoding($isi, 'UTF-8')) {
            throw new BisnisException('File harus berenkode UTF-8.');
        }

        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi); // buang BOM
        $pemisah = $this->deteksiPemisah($isi);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $isi);
        rewind($stream);

        $header = fgetcsv($stream, 0, $pemisah);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header ?: []);

        if (array_diff(self::HEADER, $header)) {
            fclose($stream);
            throw new BisnisException('Header CSV harus berisi kolom: '.implode(', ', self::HEADER).'.');
        }
        $kolom = array_flip($header);

        $kelasMap = Kelas::tahunAktif()->get()->keyBy(fn ($k) => mb_strtolower(trim($k->nama)));
        $nisAda = Siswa::withTrashed()->pluck('nis')->flip()->all();

        $berhasil = 0;
        $dilewati = 0;
        $gagal = [];
        $nomor = 1;

        while (($baris = fgetcsv($stream, 0, $pemisah)) !== false) {
            $nomor++;

            if (count($baris) === 1 && trim((string) $baris[0]) === '') {
                continue; // baris kosong
            }

            $nis = trim((string) ($baris[$kolom['nis']] ?? ''));
            $nama = trim((string) ($baris[$kolom['nama']] ?? ''));
            $namaKelas = mb_strtolower(trim((string) ($baris[$kolom['nama_kelas']] ?? '')));

            if ($nis === '' || $nama === '') {
                $gagal[] = ['baris' => $nomor, 'alasan' => 'NIS dan nama wajib diisi.'];

                continue;
            }
            if (mb_strlen($nis) > 30 || mb_strlen($nama) > 150) {
                $gagal[] = ['baris' => $nomor, 'alasan' => 'NIS atau nama terlalu panjang.'];

                continue;
            }
            if (isset($nisAda[$nis])) {
                $dilewati++;

                continue;
            }
            $kelas = $kelasMap->get($namaKelas);
            if (! $kelas) {
                $gagal[] = ['baris' => $nomor, 'alasan' => 'Kelas "'.trim((string) $baris[$kolom['nama_kelas']]).'" tidak ditemukan pada tahun ajaran aktif.'];

                continue;
            }

            Siswa::create([
                'nis' => $nis,
                'nama' => $nama,
                'kelas_id' => $kelas->id,
                'status' => 'aktif',
            ]);
            $nisAda[$nis] = true; // NIS ganda di dalam file yang sama → dilewati
            $berhasil++;
        }

        fclose($stream);

        return ['berhasil' => $berhasil, 'dilewati' => $dilewati, 'gagal' => $gagal];
    }

    private function deteksiPemisah(string $isi): string
    {
        $barisPertama = strtok($isi, "\r\n") ?: '';

        return substr_count($barisPertama, ';') > substr_count($barisPertama, ',') ? ';' : ',';
    }
}

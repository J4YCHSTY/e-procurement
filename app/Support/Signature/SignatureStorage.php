<?php

namespace App\Support\Signature;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Penyimpanan tanda tangan digital.
 *
 * Tanda tangan itu satu-satunya data di sistem ini yang kalau bocor bisa
 * dipakai memalsukan dokumen di LUAR sistem. Karena itu penyimpanannya
 * berlapis:
 *
 *  1. Berkasnya ditaruh di disk 'local' (storage/app/private) - di luar
 *     public/, jadi web server tidak punya rute ke sana sama sekali.
 *  2. Isinya dienkripsi pakai APP_KEY sebelum ditulis. Yang menyalin folder
 *     storage atau mencuri backup cuma dapat blob acak, bukan PNG yang bisa
 *     dibuka. Makanya ekstensinya .sig, bukan .png - isinya memang bukan PNG.
 *  3. Nama berkasnya ULID acak, bukan "signature_5.sig", supaya tidak ada
 *     pola yang bisa ditebak atau dienumerasi.
 *  4. Gambarnya di-encode ulang lewat GD sebelum disimpan. Ini bukan soal
 *     rapi: berkas yang mengaku PNG bisa menyelipkan payload di belakang
 *     data gambar, dan encode ulang membuang semuanya sekalian menghapus
 *     metadata EXIF.
 *
 * Kelas ini butuh ekstensi GD (sudah dinyatakan sebagai ext-gd di
 * composer.json). Kalau belum aktif, store() melempar RuntimeException
 * dengan pesan cara mengaktifkannya - bukan fatal error.
 *
 * PENTING: karena isinya terikat APP_KEY, jangan pernah menjalankan
 * `php artisan key:generate` lagi di environment yang sudah berisi tanda
 * tangan - semuanya akan jadi tidak bisa dibuka selamanya, tanpa pemulihan.
 * Cadangkan APP_KEY bersama database.
 */
class SignatureStorage
{
    private const DISK = 'local';

    private const DIRECTORY = 'signatures';

    /** Lebar maksimum gambar yang disimpan; lebih dari ini diturunkan. */
    private const MAX_WIDTH = 900;

    /** Batas ukuran berkas mentah sebelum diproses (1 MB). */
    private const MAX_BYTES = 1048576;

    /**
     * Simpan tanda tangan baru untuk seorang user, menimpa yang lama.
     *
     * @param  string  $binary  Isi berkas gambar mentah (bukan base64).
     *
     * @throws RuntimeException kalau berkasnya bukan gambar yang bisa dibaca.
     */
    public function store(User $user, string $binary): void
    {
        if (strlen($binary) > self::MAX_BYTES) {
            throw new RuntimeException('Ukuran gambar tanda tangan maksimal 1 MB.');
        }

        $png = $this->reencodeAsPng($binary);

        $previousPath = $user->signature_path;
        $path = self::DIRECTORY.'/'.Str::ulid().'.sig';

        Storage::disk(self::DISK)->put($path, Crypt::encryptString($png));

        $user->forceFill([
            'signature_path' => $path,
            'signature_uploaded_at' => now(),
        ])->save();

        // Berkas lama baru dihapus SETELAH yang baru tersimpan & tercatat,
        // biar kalau penyimpanannya gagal di tengah jalan, user tidak
        // kehilangan tanda tangan yang sudah ada.
        if ($previousPath) {
            Storage::disk(self::DISK)->delete($previousPath);
        }
    }

    /**
     * Ambil tanda tangan seorang user sebagai PNG mentah (sudah didekripsi).
     * Dekripsi terjadi di memori - tidak ada berkas PNG polos yang pernah
     * ditulis ke disk, bahkan sementara.
     */
    public function get(User $user): ?string
    {
        if (! $user->signature_path) {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($user->signature_path)) {
            return null;
        }

        return Crypt::decryptString($disk->get($user->signature_path));
    }

    /**
     * Hapus tanda tangan user beserta berkasnya.
     */
    public function forget(User $user): void
    {
        if ($user->signature_path) {
            Storage::disk(self::DISK)->delete($user->signature_path);
        }

        $user->forceFill([
            'signature_path' => null,
            'signature_uploaded_at' => null,
        ])->save();
    }

    /**
     * Baca gambar apa pun yang didukung GD, lalu tulis ulang jadi PNG bersih
     * dengan latar transparan dipertahankan.
     *
     * Transparansi wajib dijaga: kalau alpha-nya hilang, tanda tangan akan
     * jadi kotak putih yang menutupi garis tabel waktu ditempel ke dokumen.
     */
    private function reencodeAsPng(string $binary): string
    {
        // Dicek dulu supaya kalau ekstensinya belum aktif, yang muncul pesan
        // yang bisa ditindaklanjuti - bukan fatal error "Call to undefined
        // function" yang bikin halamannya blank 500.
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException(
                'Ekstensi GD belum aktif di PHP, jadi gambar tanda tangan belum bisa diproses. '
                .'Buka php.ini, hapus tanda titik koma di depan baris "extension=gd", lalu restart web server-nya.'
            );
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            throw new RuntimeException('Berkas tidak bisa dibaca sebagai gambar.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 1 || $height < 1) {
            imagedestroy($image);

            throw new RuntimeException('Ukuran gambar tanda tangan tidak wajar.');
        }

        if ($width > self::MAX_WIDTH) {
            $image = $this->downscale($image, $width, $height);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();

        imagedestroy($image);

        return $png;
    }

    /**
     * @param  \GdImage  $image
     * @return \GdImage
     */
    private function downscale($image, int $width, int $height)
    {
        $targetHeight = (int) round($height * (self::MAX_WIDTH / $width));
        $resized = imagecreatetruecolor(self::MAX_WIDTH, max($targetHeight, 1));

        // Kanvas tujuan diisi warna transparan penuh dulu, kalau tidak
        // hasilnya berlatar hitam.
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));

        imagecopyresampled(
            $resized, $image,
            0, 0, 0, 0,
            self::MAX_WIDTH, max($targetHeight, 1),
            $width, $height
        );

        imagedestroy($image);

        return $resized;
    }
}

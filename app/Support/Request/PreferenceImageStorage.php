<?php

namespace App\Support\Request;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Penyimpanan gambar preferensi barang (tangkapan layar dari marketplace).
 *
 * Sama seperti tanda tangan, berkasnya ditaruh di disk 'local'
 * (storage/app/private) supaya tidak bisa diakses langsung dari browser, dan
 * hanya bisa dibuka lewat route yang mengecek RequestPolicy::view.
 *
 * Bedanya dengan tanda tangan: isinya TIDAK dienkripsi. Alasannya bukan
 * malas - tanda tangan dienkripsi karena kalau bocor bisa dipakai memalsukan
 * dokumen di luar sistem, sedangkan tangkapan layar produk tidak punya risiko
 * itu. Enkripsi di sini cuma menambah beban baca tanpa menambah perlindungan
 * yang berarti.
 *
 * Yang tetap dilakukan: gambarnya di-encode ulang lewat GD. Berkas yang
 * mengaku PNG bisa menyelipkan payload di belakang data gambar, dan encode
 * ulang membuang semuanya sekalian menghapus metadata EXIF (yang pada foto
 * bisa memuat lokasi).
 */
class PreferenceImageStorage
{
    private const DISK = 'local';

    private const DIRECTORY = 'request-preferences';

    /** Tangkapan layar tidak perlu lebih lebar dari ini. */
    private const MAX_WIDTH = 1200;

    /**
     * Simpan gambar preferensi, kembalikan path relatifnya.
     *
     * @throws RuntimeException kalau berkasnya bukan gambar yang bisa dibaca.
     */
    public function store(UploadedFile $file): string
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException(
                'Ekstensi GD belum aktif di PHP, jadi gambar preferensi belum bisa diproses. '
                .'Buka php.ini, hapus tanda titik koma di depan baris "extension=gd", lalu restart web server-nya.'
            );
        }

        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw new RuntimeException('Berkas preferensi tidak bisa dibaca sebagai gambar.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > self::MAX_WIDTH) {
            $targetHeight = max(1, (int) round($height * (self::MAX_WIDTH / $width)));
            $resized = imagecreatetruecolor(self::MAX_WIDTH, $targetHeight);

            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagecopyresampled($resized, $image, 0, 0, 0, 0, self::MAX_WIDTH, $targetHeight, $width, $height);

            imagedestroy($image);
            $image = $resized;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagepng($image, null, 8);
        $png = (string) ob_get_clean();

        imagedestroy($image);

        $path = self::DIRECTORY.'/'.Str::ulid().'.png';
        Storage::disk(self::DISK)->put($path, $png);

        return $path;
    }

    public function get(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        return $disk->exists($path) ? $disk->get($path) : null;
    }

    public function forget(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}

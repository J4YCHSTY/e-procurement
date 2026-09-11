<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Jangan render aset Vite betulan waktu testing - kalau public/build
         * belum pernah dibuat, Blade langsung melempar error "manifest tidak
         * ketemu" padahal yang mau diuji cuma logikanya.
         */
        $this->withoutVite();

        /*
         * Samakan "versi aset" yang dikirim test dengan yang dihitung server.
         *
         * Inertia menandai setiap respons dengan hash manifest build, lalu
         * membalas 409 Conflict kalau klien mengirim versi yang beda - itu
         * mekanisme normal supaya browser dengan aset basi memuat ulang
         * halaman. Masalahnya, request test mengirim header X-Inertia tanpa
         * X-Inertia-Version sama sekali, jadi selalu dianggap basi. Akibatnya
         * seluruh test halaman berubah merah BEGITU `npm run build` pernah
         * dijalankan - hijau di komputer yang belum pernah build, merah di
         * yang sudah. Dengan header ini, test berlaku seperti browser yang
         * asetnya sudah terbaru.
         */
        $manifest = public_path('build/manifest.json');

        if (file_exists($manifest)) {
            $this->withHeader('X-Inertia-Version', hash_file('xxh128', $manifest));
        }
    }
}

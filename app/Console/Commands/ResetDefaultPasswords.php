<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Command sekali-pakai buat migrasi dari alur login lama (simulasi SSO,
 * password nggak pernah dicek) ke alur login baru yang beneran ngecek
 * password lewat Auth::attempt().
 *
 * Akun-akun yang udah ada di database (dari seeder lama) masih punya hash
 * password yang lama, jadi begitu login mulai beneran ngecek password,
 * mereka nggak akan bisa login pakai password default yang baru (lihat
 * DEFAULT_USER_PASSWORD di .env / App\Models\User::defaultPassword())
 * kalau nggak di-reset dulu lewat command ini.
 *
 * Sengaja dibikin command, bukan migration, karena ini perubahan DATA
 * (bukan skema) dan cuma perlu dijalankan sekali secara manual - beda
 * kasus sama migration yang otomatis jalan tiap deploy/migrate.
 */
class ResetDefaultPasswords extends Command
{
    protected $signature = 'users:reset-default-password
                            {--password= : Password baru buat semua akun (default: dari .env DEFAULT_USER_PASSWORD)}
                            {--force : Lewati konfirmasi (buat dipakai di script/non-interaktif)}';

    protected $description = 'Reset password SEMUA akun ke satu password default. Dipakai sekali aja pas migrasi dari alur login SSO-simulasi ke alur login berbasis password beneran.';

    public function handle(): int
    {
        $password = $this->option('password') ?? User::defaultPassword();
        $count = User::count();

        if ($count === 0) {
            $this->warn('Belum ada akun sama sekali di database.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Ini bakal nge-reset password SEMUA {$count} akun (termasuk yang udah pernah diganti manual lewat halaman Profil) jadi \"{$password}\". Lanjut?"
        )) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        User::query()->update(['password' => Hash::make($password)]);

        $this->info("Selesai. Password {$count} akun sudah direset ke \"{$password}\".");

        return self::SUCCESS;
    }
}

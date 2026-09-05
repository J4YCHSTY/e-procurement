<?php

namespace App\Console\Commands;

use App\Models\Departement;
use App\Models\User;
use App\Support\Xlsx\SimpleXlsxReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

/**
 * Import/update data karyawan dari file Excel jadi akun login di
 * master_employees. Dipakai pertama kali buat migrasi data karyawan asli
 * (ganti akun dummy demo), dan bisa dipakai lagi ke depannya tiap kali ada
 * karyawan baru masuk atau data yang perlu di-update - upsert berdasarkan
 * email, jadi aman dijalankan berkali-kali pakai file yang sama atau file
 * baru (nggak bikin duplikat).
 *
 * Kolom yang dibaca dicari berdasarkan NAMA HEADER (bukan posisi kolom),
 * jadi nggak masalah kalau urutan kolom di file Excel-nya beda-beda:
 *   - Nama / Name            (wajib)
 *   - Email                  (wajib)
 *   - Jabatan / Position     (opsional)
 *   - Perusahaan / Entity    (opsional)
 *   - Departemen / Department (opsional - kalau nama departemennya belum
 *     ada di sistem, otomatis dibuatkan baru)
 *   - Role / Role Approval   (opsional, default 'user' kalau kosong atau
 *     nilainya nggak dikenal - harus salah satu dari: user, head, it,
 *     finance, procurement)
 *
 * Baris tanpa email yang valid otomatis dilewati (misal staff yang belum
 * punya email kantor).
 */
class ImportEmployees extends Command
{
    protected $signature = 'employees:import
        {file : Path ke file .xlsx (relatif dari folder project, atau path absolut)}
        {--sheet= : Nama sheet yang mau dibaca (default: sheet pertama di file)}
        {--dry-run : Cuma preview hasil baca & validasi, tidak menyimpan apapun ke database}';

    protected $description = 'Import/update data karyawan dari file Excel jadi akun login (upsert berdasarkan email)';

    private const VALID_ROLES = ['user', 'head', 'it', 'finance', 'procurement'];

    /**
     * Alias nama header yang dikenali per kolom, semua dibandingkan dalam
     * huruf kecil & sudah di-trim. Tambahkan alias baru di sini kalau
     * suatu saat format header file HR-nya berubah.
     */
    private const HEADER_ALIASES = [
        'name' => ['nama', 'name'],
        'position' => ['jabatan', 'position'],
        'entity' => ['perusahaan', 'perusahaan (entity)', 'entity', 'company'],
        'email' => ['email'],
        'department' => ['departemen sistem', 'departemen', 'department', 'dept'],
        'role' => ['role approval', 'role'],
    ];

    public function handle(): int
    {
        $path = $this->resolvePath($this->argument('file'));

        if (! is_file($path)) {
            $this->components->error("File tidak ketemu: {$path}");

            return self::FAILURE;
        }

        try {
            $defaultPassword = User::defaultPassword();
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            $rows = SimpleXlsxReader::rows($path, $this->option('sheet'));
        } catch (Throwable $e) {
            $this->components->error('Gagal membaca file: '.$e->getMessage());

            return self::FAILURE;
        }

        if (count($rows) < 2) {
            $this->components->error('File kosong atau cuma berisi baris header.');

            return self::FAILURE;
        }

        $header = array_shift($rows);
        $columnIndex = $this->mapHeader($header);

        foreach (['name', 'email'] as $required) {
            if (! isset($columnIndex[$required])) {
                $this->components->error(
                    "Kolom wajib '{$required}' tidak ketemu di header. Header yang kebaca: ".implode(', ', array_filter($header))
                );

                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');

        $created = 0;
        $updated = 0;
        $skipped = [];
        $warnings = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $i => $row) {
                $rowNumber = $i + 2; // +2: index 0 based + 1 baris header yang sudah dibuang

                $value = fn (string $key): string => isset($columnIndex[$key])
                    ? trim((string) ($row[$columnIndex[$key]] ?? ''))
                    : '';

                $name = $value('name');
                $email = $this->cleanEmail($value('email'));
                $position = $value('position') ?: null;
                $entity = $value('entity') ?: null;
                $departmentName = $value('department');
                $role = strtolower($value('role')) ?: 'user';

                if ($name === '' && $email === '') {
                    continue; // baris kosong total (spasi kosong di antara data), lewati diam-diam
                }

                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped[] = "Baris {$rowNumber} ({$name}): tidak ada email kantor yang valid.";

                    continue;
                }

                if (! in_array($role, self::VALID_ROLES, true)) {
                    $warnings[] = "Baris {$rowNumber} ({$name}): role '{$role}' tidak dikenal, dipakai 'user'.";
                    $role = 'user';
                }

                $departementId = null;
                if ($departmentName !== '') {
                    $departement = Departement::whereRaw('LOWER(name) = ?', [strtolower($departmentName)])->first();

                    if (! $departement) {
                        $departement = Departement::create(['name' => $departmentName]);
                        $warnings[] = "Departemen baru '{$departmentName}' otomatis dibuat (baris {$rowNumber}).";
                    }

                    $departementId = $departement->id;
                }

                $attributes = [
                    'name' => $name !== '' ? $name : $email,
                    'email' => $email,
                    'position' => $position,
                    'entity' => $entity,
                    'departement_id' => $departementId,
                    'role' => $role,
                ];

                $existing = User::where('email', $email)->first();

                if ($existing) {
                    $existing->update($attributes);
                    $updated++;
                } else {
                    User::create([
                        ...$attributes,
                        'password' => Hash::make($defaultPassword),
                        'email_verified_at' => now(),
                        'is_active' => true,
                    ]);
                    $created++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (Throwable $e) {
            DB::rollBack();
            $this->components->error('Import dibatalkan, tidak ada perubahan yang disimpan: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info($dryRun ? 'DRY RUN selesai - tidak ada perubahan yang disimpan.' : 'Import selesai.');

        $this->table(['Ringkasan', 'Jumlah'], [
            ['Akun baru dibuat', $created],
            ['Akun yang sudah ada, diperbarui', $updated],
            ['Baris dilewati (tanpa email valid)', count($skipped)],
        ]);

        if ($warnings !== []) {
            $this->newLine();
            $this->components->warn('Perhatian:');
            foreach ($warnings as $warning) {
                $this->line("  - {$warning}");
            }
        }

        if ($skipped !== []) {
            $this->newLine();
            $this->components->warn('Baris yang dilewati:');
            foreach ($skipped as $reason) {
                $this->line("  - {$reason}");
            }
        }

        if (! $dryRun && $created > 0) {
            $this->newLine();
            $this->components->info(
                "Password default untuk {$created} akun baru: {$defaultPassword} - arahkan karyawan ganti password sendiri lewat halaman Profil setelah login pertama."
            );
        }

        return self::SUCCESS;
    }

    private function resolvePath(string $file): string
    {
        $isAbsolute = str_starts_with($file, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $file) === 1;

        return $isAbsolute ? $file : base_path($file);
    }

    /**
     * @param  list<string|null>  $headerRow
     * @return array<string, int>
     */
    private function mapHeader(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $label) {
            $normalized = strtolower(trim((string) $label));

            foreach (self::HEADER_ALIASES as $key => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $map[$key] = $index;
                }
            }
        }

        return $map;
    }

    /**
     * Beberapa export Excel/HRIS suka kebawa karakter liar di ekor email
     * (spasi, backslash tersasar, dsb) - dibersihkan sebelum divalidasi.
     */
    private function cleanEmail(string $email): string
    {
        return rtrim(trim($email), "\\/.,; ");
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Tabel auth digabung dengan data karyawan (master_employees),
     * biar nggak ada dua sumber data buat satu orang yang sama.
     */
    protected $table = 'master_employees';

    /**
     * Password default buat akun baru (dibuat IT lewat User Management)
     * atau buat reset password (baik lewat User Management maupun command
     * `users:reset-default-password`). User diarahkan ganti sendiri lewat
     * halaman Profil setelah login pertama kali.
     *
     * Nilainya sengaja diambil dari .env (DEFAULT_USER_PASSWORD), BUKAN
     * di-hardcode di sini, biar nggak ke-push ke Git sebagai plaintext
     * credential. Kalau belum diatur di .env, method ini bakal throw
     * biar ketauan dari awal daripada diam-diam bikin akun tanpa password.
     */
    public static function defaultPassword(): string
    {
        return config('app.default_user_password')
            ?? throw new \RuntimeException(
                'DEFAULT_USER_PASSWORD belum diatur di file .env. Tambahkan baris DEFAULT_USER_PASSWORD=... sebelum membuat/reset akun.'
            );
    }

    /**
     * Peran yang menentukan posisi seseorang di rantai pengajuan.
     *
     * Key = nilai yang disimpan di kolom `role`, value = label yang dipakai
     * di dropdown Manajemen User dan badge di tabel.
     *
     *   user    - mengajukan saja, tidak memproses pengajuan siapapun
     *   head    - kepala departemen; menyetujui pengajuan anak buahnya sendiri
     *   it_head - Head of IT; menilai kelayakan teknis, lintas departemen
     *   it      - IT Admin; memegang berkas selama diproses di luar sistem,
     *             lalu menandainya selesai waktu barangnya sampai
     *
     * Daftarnya dikunci di sini, bukan di enum kolom database, supaya menambah
     * peran baru cukup lewat kode dan tidak perlu ALTER TABLE - pelajaran dari
     * waktu kolomnya masih enum dan menambah it_head jadi butuh migrasi.
     */
    public const ROLES = [
        'user' => 'User',
        'head' => 'Kepala Departemen',
        'it_head' => 'Head of IT',
        'it' => 'IT Admin',
    ];

    /**
     * Daftar peran dalam bentuk siap pakai buat dropdown di frontend.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function roleOptions(): array
    {
        $options = [];

        foreach (self::ROLES as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    /**
     * Perusahaan (entity) yang ada di grup Visinema.
     *
     * Sengaja dikunci sebagai daftar tetap di sini - BUKAN tabel terpisah
     * kayak Departement - karena jumlahnya memang cuma tiga dan nggak nambah
     * seiring waktu, persis kayak daftar role approval. Departemen beda
     * ceritanya: itu bisa nambah/pecah, makanya dia punya tabel sendiri.
     *
     * Key   = nilai yang beneran disimpan di kolom `entity`. Hurufnya besar
     *         semua, ngikutin format file HR yang diimpor lewat
     *         `php artisan employees:import` - jadi data lama tetap cocok.
     * Value = label yang ditampilkan di dropdown.
     *
     * Urutannya ngikutin jumlah karyawan (VP paling banyak) biar yang paling
     * sering dipilih ada di paling atas.
     */
    public const ENTITIES = [
        'PT VISINEMA PICTURES' => 'PT Visinema Pictures (VP)',
        'PT VISINEMA KONTEN INDONESIA' => 'PT Visinema Konten Indonesia (VKI)',
        'PT BIOSKOP DIGITAL INDONESIA' => 'PT Bioskop Digital Indonesia (BDI)',
    ];

    /**
     * Daftar perusahaan dalam bentuk siap pakai buat dropdown di frontend.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function entityOptions(): array
    {
        $options = [];

        foreach (self::ENTITIES as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    /**
     * Samakan penulisan nama perusahaan ke format resmi kalau bedanya cuma
     * huruf besar/kecil (misal file HR nulis "PT Visinema Pictures").
     * Kalau memang nggak ada di daftar, dikembalikan apa adanya biar
     * datanya nggak hilang - yang manggil yang mutusin mau diapain.
     */
    public static function normalizeEntity(?string $entity): ?string
    {
        if ($entity === null || trim($entity) === '') {
            return null;
        }

        $entity = trim($entity);

        foreach (array_keys(self::ENTITIES) as $official) {
            if (strcasecmp($entity, $official) === 0) {
                return $official;
            }
        }

        return $entity;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'entity',
        'position',
        'departement_id',
        'role',
        'can_manage_users',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Nama berkas tanda tangan tidak perlu bocor ke frontend. Yang
        // dibutuhkan React cuma tahu SUDAH PUNYA atau belum, lihat $appends.
        'signature_path',
    ];

    /**
     * Ikut dikirim ke frontend setiap kali User diserialisasi.
     *
     * @var list<string>
     */
    protected $appends = [
        'has_signature',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'can_manage_users' => 'boolean',
            'signature_uploaded_at' => 'datetime',
        ];
    }

    /**
     * Sudah punya tanda tangan digital atau belum.
     *
     * Dipakai frontend buat memutuskan apakah perlu memunculkan gerbang
     * "unggah tanda tangan dulu" sebelum tombol kirim/approve. Sengaja
     * dikirim sebagai boolean, bukan gambarnya - gambar tanda tangan tidak
     * pernah ikut ke payload halaman.
     */
    protected function hasSignature(): Attribute
    {
        return Attribute::get(fn (): bool => $this->signature_path !== null);
    }

    /**
     * Departemen tempat karyawan ini bernaung.
     * Dipakai nanti buat filter approval per departemen.
     */
    public function departement()
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }
}

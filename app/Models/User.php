<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
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
        ];
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

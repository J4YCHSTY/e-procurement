# Rekap Update Sistem E-Procurement

Dokumen ini merangkum semua perubahan yang sudah dikerjakan di aplikasi e-procurement sejauh ini, biar mudah dijadikan referensi (misalnya buat bagian implementasi di skripsi).

## 1. Landing Page Dihapus

Halaman "Welcome" (landing page marketing) sudah tidak dipakai lagi. Sekarang saat user mengakses root URL (`/`), sistem langsung mengarahkan ke halaman Login (atau ke Dashboard kalau user sudah login).

## 2. Logo Perusahaan

- Logo kotak huruf "E" placeholder diganti dengan logo asli perusahaan (Visinema Pictures).
- Tampilan logo dibuat clean: hanya gambar logo, tanpa teks "E-Procurement / Sistem Pengajuan" di sampingnya.
- Area logo di sidebar diperbesar supaya lebih rapi dan proporsional.
- Logo juga ikut diperbarui di halaman login (Guest Layout).

## 3. Pembersihan Visual Dashboard

- Icon di stat card (Total Pengajuan, Sedang Diproses, dll) diubah jadi icon hitam polos, tanpa kotak/outline warna di belakangnya.
- Badge status pengajuan (Menunggu Approval, Disetujui, Ditolak, dst) diubah dari pill berwarna jadi dot kecil + teks berwarna, tanpa background bulat.
- Badge kategori (Hardware/Software) di tabel riwayat dan approval juga disamakan gayanya: icon + teks berwarna, tanpa outline/pill.

## 4. Fitur Riwayat Approval (Audit Trail)

Tab baru "Riwayat Approval" ditambahkan di dashboard Head, IT, dan Finance, supaya mereka bisa melihat semua pengajuan yang pernah mereka proses beserta keputusannya (disetujui/ditolak/ditandai selesai).

- Sistem sekarang mencatat siapa yang menolak, kapan, dan di tahap approval mana penolakan terjadi (kolom baru: `rejected_by_id`, `rejected_at`, `rejected_at_stage`).
- Logikanya sudah diuji lewat 9 test khusus, termasuk kasus penting: kalau Head sudah approve tapi ditolak belakangan oleh IT/Finance, riwayat Head tetap menunjukkan hal itu dengan jelas (bukan hilang begitu saja).

## 5. Login Diubah ke Password Asli

Sebelumnya sistem cuma pakai simulasi SSO (login modal-modalan cuma pakai email, tanpa cek password sama sekali). Sekarang:

- Login sudah pakai password sungguhan.
- Semua akun punya password default yang diatur lewat `.env` (variabel `DEFAULT_USER_PASSWORD`), bukan ditulis langsung di kode.
- User yang sudah login bisa ganti password sendiri lewat halaman Profil.
- Fitur "Lupa Password" (kirim link reset lewat email) sudah tersedia di sisi kode, tapi belum aktif karena konfigurasi SMTP-nya sengaja ditunda dulu sesuai arahan.

## 6. Fitur User Management (Khusus Role IT)

Menu baru "Manajemen User" muncul di sidebar, hanya terlihat oleh akun dengan role IT. Fiturnya:

- **Lihat & edit profil user** — nama, email, perusahaan, jabatan, departemen, dan role bisa diubah.
- **Tambah user baru** — akun baru otomatis dapat password default.
- **Reset password** — IT bisa mengembalikan password user manapun ke password default kalau user lupa/butuh dibantu.
- **Nonaktifkan/aktifkan akun** — akun yang tidak aktif tidak bisa login lagi, tapi data riwayat pengajuannya tetap tersimpan (bukan dihapus permanen, biar data approval nggak hilang).

Ada juga dua pengaman supaya IT tidak salah pencet dan mengunci diri sendiri dari sistem:
- IT tidak bisa mengubah role akunnya sendiri lewat menu ini.
- IT tidak bisa menonaktifkan akunnya sendiri.

Fitur ini sudah diuji lewat 15 test otomatis (otorisasi, validasi, dan alur kerja), dan hasil regresi keseluruhan sistem (42 test lain) tetap lolos setelah perubahan ini.

## Yang Masih Perlu Dilakukan Manual

1. Jalankan `php artisan migrate` di lokal untuk menerapkan kolom baru (`is_active`, kolom audit trail approval) ke database.
2. Setup SMTP (misalnya Gmail SMTP) kalau nanti mau mengaktifkan fitur "Lupa Password" lewat email — saat ini sengaja ditunda dulu.

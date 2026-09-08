# Dokumentasi Backend - E-Procurement

Ringkas, fokus ke skema database dan alur kerja backend. Diagram (ERD, flowchart) menyusul terpisah.

**Stack:** Laravel 12 (backend + auth + otorisasi) + Inertia.js v2 (jembatan) + React 18 (frontend). Satu request dari browser diproses penuh oleh Controller Laravel, hasilnya dikirim ke komponen React sebagai "page props" - tidak ada REST API terpisah.

---

## 1. Skema Database

### `departements`
Daftar departemen. Di-seed 5 baris: IT, Finance, Procurement, HR, Operations.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | string | |

### `master_employees` (tabel auth, dipakai lewat model `User`)
Satu tabel merangkap dua fungsi: data karyawan DAN akun login. Login pakai email karyawan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name, email (unique), password | | Standar auth |
| entity, position | string, nullable | Data karyawan (perusahaan, jabatan) |
| departement_id | FK -> departements, nullable | `onDelete: set null` |
| role | enum: user/head/it/finance/procurement | **Menentukan tahap approval** yang jadi jatah user ini |
| can_manage_users | boolean, default false | **Terpisah dari role** - menentukan akses menu Manajemen User |
| is_active | boolean, default true | Akun nonaktif diblokir login (dicek di `LoginRequest`), tapi datanya tetap ada (histori pengajuan tidak ikut hilang) |

Kenapa `role` dan `can_manage_users` dipisah: awalnya akses menu User Management nempel ke `role === 'it'`. Ternyata butuh kondisi orang punya akses menu itu TANPA ikut approval apapun (dan sebaliknya, orang tetap ikut approval tanpa akses menu). Karena `role` cuma bisa satu nilai sekaligus, dua kebutuhan ini dipisah jadi dua kolom independen.

### `hardware_requests` & `software_requests`
Struktur mirip, dua tabel terpisah karena field spesifiknya beda (software butuh jumlah lisensi, hardware tidak, dst).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | FK -> master_employees | `onDelete: cascade` - pengajuan ikut terhapus kalau akunnya dihapus permanen (makanya nonaktif akun dipilih daripada hapus) |
| request_date, justification, digital_signature | | Field umum |
| **khusus hardware:** hardware_type, hardware_recommendation | | |
| **khusus software:** software_name, software_type, software_usage, license_count, duration_months, estimated_cost | | |
| status | string (nilai dari enum `RequestStatus`) | Posisi pengajuan saat ini di alur approval |
| rejected_by_id | bigint, nullable, tanpa FK constraint | Siapa yang menolak (dipakai untuk riwayat) |
| rejected_at | timestamp, nullable | Kapan ditolak |
| rejected_at_stage | string, nullable | Snapshot tahap approval saat ditolak (misal `WAITING_FOR_IT_APPROVAL`) - supaya riwayat tetap bisa bedain "ditolak Head" vs "ditolak IT" walau status akhirnya sama-sama `REJECTED` |

---

## 2. Alur Approval

Status pengajuan (enum `App\Enums\RequestStatus`), berurutan linear untuk hardware maupun software:

```
WAITING_FOR_HEAD_APPROVAL -> WAITING_FOR_IT_APPROVAL -> WAITING_FOR_FINANCE_APPROVAL -> APPROVED -> COMPLETED
                                                                                              |
                                                                                    (bisa REJECTED di tahap manapun,
                                                                                     kecuali saat sudah APPROVED)
```

- **Head**: approve khusus pengajuan dari departemennya sendiri (dicek `departement_id` requester = `departement_id` approver). Satu departemen idealnya satu head.
- **IT**, **Finance**: approve LINTAS departemen (approver global, cukup 1 orang per role idealnya).
- **Procurement**: bukan approval, tapi eksekusi administratif - menandai barang/lisensi sudah diserahkan (`APPROVED` -> `COMPLETED`). Tahap ini tidak bisa direject.

Siapa boleh approve di tahap apa diatur di `App\Policies\RequestPolicy` (satu policy dipakai bareng untuk `HardwareRequest` & `SoftwareRequest`, didaftarkan di `AppServiceProvider`). Method kuncinya: `approve()` (cek role user cocok dengan tahap status pengajuan saat ini) dan `reject()` (aturan sama, kecuali tahap `APPROVED`).

Alur kode saat approve/reject: `ApprovalController` -> `$this->authorize()` panggil `RequestPolicy` -> kalau lolos, status pengajuan dimajukan ke `next()` (approve) atau di-set `REJECTED` + disnapshot `rejected_at_stage`/`rejected_by_id`/`rejected_at` (reject).

---

## 3. Otorisasi: Approval vs Manajemen User

Dua sistem otorisasi independen, jangan tertukar:

| | Ditentukan oleh | Policy | Dicek di |
|---|---|---|---|
| Ikut approval pengajuan atau tidak | kolom `role` | `RequestPolicy` | `ApprovalController` |
| Akses menu Manajemen User atau tidak | kolom `can_manage_users` | `UserPolicy` | `UserManagementController` |

`UserPolicy` isinya sederhana: semua method (`viewAny`, `create`, `updateProfile`, `resetPassword`) cuma cek `$actor->can_manage_users`, kecuali `toggleActive` yang tambah syarat tidak menonaktifkan akun sendiri. Ada juga proteksi *self-lockout* di `UserManagementController::update()`: seseorang tidak bisa mengubah `role` ATAU `can_manage_users` di akun miliknya sendiri lewat form ini - supaya tidak ada yang tidak sengaja mencabut akses satu-satunya admin yang tersisa.

`DashboardController::statusToReviewFor()` adalah titik pusat yang menerjemahkan `role` user jadi tahap approval yang jadi jatahnya (dipakai untuk filter data pending approval + riwayat approval + flag `canApprove` yang menentukan tab Approval muncul di frontend atau tidak).

---

## 4. Peta Struktur Kode

| Lapisan | File | Isi |
|---|---|---|
| Model | `app/Models/User.php` | Auth + data karyawan (tabel `master_employees`) |
| | `app/Models/Departement.php`, `HardwareRequest.php`, `SoftwareRequest.php` | |
| Enum | `app/Enums/RequestStatus.php` | Semua status pengajuan + urutan tahap (`next()`, `order()`) |
| Policy | `app/Policies/RequestPolicy.php` | Aturan approve/reject pengajuan |
| | `app/Policies/UserPolicy.php` | Aturan akses Manajemen User |
| Controller | `app/Http/Controllers/RequestController.php` | Bikin pengajuan baru (karyawan) |
| | `app/Http/Controllers/ApprovalController.php` | Approve/reject pengajuan |
| | `app/Http/Controllers/DashboardController.php` | Data dashboard: histori sendiri, pending approval, riwayat approval |
| | `app/Http/Controllers/UserManagementController.php` | CRUD akun karyawan (untuk yang `can_manage_users = true`) |
| Command | `app/Console/Commands/ImportEmployees.php` | Import/update data karyawan dari Excel (upsert by email) |
| Frontend | `resources/js/Pages/Dashboard.jsx` | Form pengajuan + tab approval + riwayat |
| | `resources/js/Pages/Users/Index.jsx` | Halaman Manajemen User |
| | `resources/js/Layouts/AuthenticatedLayout.jsx` | Sidebar - render menu "Manajemen User" berdasar `can_manage_users` |

## 5. Routes Ringkas (`routes/web.php`)

Semua di belakang middleware `auth`:

- `POST /request/hardware`, `POST /request/software` - karyawan bikin pengajuan baru.
- `POST /request/{jenis}/{id}/approve`, `.../reject` - approve/reject (otorisasi lewat `RequestPolicy`).
- `GET/POST/PATCH /users...` - Manajemen User (otorisasi lewat `UserPolicy`, dicek per-method di controller, bukan di route).

---

*Catatan: dokumentasi ini mengikuti kondisi kode per 5 September 2026 (setelah fitur `can_manage_users` ditambahkan). Kalau ada perubahan struktur berikutnya, kabari saya supaya dokumentasi ini diperbarui.*

<?php

namespace App\Console\Commands;

use App\Enums\RequestStatus;
use App\Models\Departement;
use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use App\Models\User;
use App\Support\Request\ApprovalChain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * Menjalankan satu pengajuan menembus seluruh tahap approval, langsung lewat
 * model dan RequestPolicy - tanpa membuka browser, tanpa login, tanpa server.
 *
 * Gunanya buat memastikan alurnya benar tanpa harus klik satu per satu di
 * layar pakai lima akun berbeda. Yang dicek bukan cuma "statusnya berubah
 * atau tidak", tapi juga siapa yang BOLEH dan siapa yang DITOLAK di tiap
 * tahap - karena di situlah letak aturan sebenarnya.
 *
 * Semua data yang dibuat di sini dijalankan di dalam transaksi dan di-rollback
 * begitu selesai, jadi database aslinya tidak tersentuh sama sekali. Pakai
 * --keep kalau memang mau datanya tetap tersimpan buat dilihat di layar.
 *
 * Contoh:
 *   php artisan flow:simulate
 *   php artisan flow:simulate --type=software
 *   php artisan flow:simulate --reject=it_head
 *   php artisan flow:simulate --keep
 */
class SimulateRequestFlow extends Command
{
    protected $signature = 'flow:simulate
        {--type=hardware : Jenis pengajuan: hardware atau software}
        {--reject= : Simulasikan penolakan di tahap ini: head atau it_head}
        {--keep : Jangan rollback - biarkan data simulasi tersimpan di database}';

    protected $description = 'Jalankan satu pengajuan menembus semua tahap approval tanpa membuka browser';

    private array $findings = [];

    public function handle(): int
    {
        $type = strtolower((string) $this->option('type'));

        if (! in_array($type, ['hardware', 'software'], true)) {
            $this->components->error("Jenis '{$type}' tidak dikenal. Pakai hardware atau software.");

            return self::FAILURE;
        }

        $rejectAt = $this->option('reject')
            ? strtolower((string) $this->option('reject'))
            : null;

        // Cuma dua tahap awal yang berupa keputusan. ON_EXTERNAL_PROCESS
        // tidak bisa ditolak - lihat catatan di RequestPolicy::reject().
        if ($rejectAt !== null && ! in_array($rejectAt, ['head', 'it_head'], true)) {
            $this->components->error("Tahap penolakan '{$rejectAt}' tidak dikenal. Pakai head atau it_head.");

            return self::FAILURE;
        }

        $keep = (bool) $this->option('keep');

        DB::beginTransaction();

        try {
            $this->simulate($type, $rejectAt);
        } catch (Throwable $e) {
            DB::rollBack();
            $this->components->error('Simulasi berhenti: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($keep) {
            DB::commit();
            $this->newLine();
            $this->components->warn('--keep dipakai: data simulasi TERSIMPAN di database.');
        } else {
            DB::rollBack();
            $this->newLine();
            $this->components->info('Semua data simulasi sudah di-rollback. Database aslinya tidak tersentuh.');
        }

        return self::SUCCESS;
    }

    private function simulate(string $type, ?string $rejectAt): void
    {
        $cast = $this->buildCast();
        $request = $this->createRequest($type, $cast['pemohon']);

        $this->newLine();
        $this->components->info('Pemeran simulasi');
        $this->table(
            ['Peran', 'Nama', 'Role', 'Departemen'],
            collect($cast)->map(fn (User $u) => [
                $u->position,
                $u->name,
                $u->role,
                $u->departement?->name ?? '-',
            ])->values()->all()
        );

        $this->components->info(
            ucfirst($type).' diajukan oleh '.$cast['pemohon']->name.
            ' - status awal: '.RequestStatus::from($request->status)->label()
        );

        // Dua tahap pertama berupa keputusan, sisanya pencatatan perjalanan
        // barang. 'ability' dibedakan karena tahap terakhir bukan approval:
        // yang menutup alurnya adalah PEMOHON lewat tanda tangan BAST.
        $stages = [
            ['key' => 'head', 'label' => '1. Kepala Departemen', 'actor' => $cast['head'], 'ability' => 'approve'],
            ['key' => 'it_head', 'label' => '2. Head of IT', 'actor' => $cast['it_head'], 'ability' => 'approve'],
            ['key' => 'it', 'label' => '3. IT Admin (barang dikirim)', 'actor' => $cast['it'], 'ability' => 'approve'],
            ['key' => 'it', 'label' => '4. IT Admin (serah terima)', 'actor' => $cast['it'], 'ability' => 'approve'],
            ['key' => 'pemohon', 'label' => '5. Pemohon (tanda tangan BAST)', 'actor' => $cast['pemohon'], 'ability' => 'signBast'],
        ];

        $rows = [];

        foreach ($stages as $stage) {
            $before = RequestStatus::from($request->status);
            $isRejecting = $rejectAt === $stage['key'];
            $ability = $isRejecting ? 'reject' : $stage['ability'];

            $allowed = Gate::forUser($stage['actor'])->allows($ability, $request);

            if (! $allowed) {
                $rows[] = [
                    $stage['label'],
                    $stage['actor']->name,
                    $before->label(),
                    $ability,
                    'DITOLAK POLICY',
                    '-',
                ];

                $this->findings[] = "Tahap {$stage['label']} tidak bisa dijalankan oleh {$stage['actor']->name} - alurnya mandek di sini.";

                break;
            }

            $isRejecting
                ? $this->applyReject($request, $stage['actor'])
                : $this->applyAdvance($request, $stage['actor']);

            $rows[] = [
                $stage['label'],
                $stage['actor']->name,
                $before->label(),
                $ability,
                'boleh',
                RequestStatus::from($request->status)->label(),
            ];

            if ($isRejecting) {
                break;
            }
        }

        $this->newLine();
        $this->components->info('Jalannya pengajuan');
        $this->table(
            ['Tahap', 'Pelaku', 'Status sebelum', 'Aksi', 'Hasil cek policy', 'Status sesudah'],
            $rows
        );

        $this->runGuardChecks($request, $cast, $type);
        $this->printFindings();
    }

    /**
     * Cek pengaman: hal-hal yang HARUS ditolak policy. Kalau ada satu saja
     * yang lolos, berarti ada lubang di aturan otorisasinya.
     */
    private function runGuardChecks(HardwareRequest|SoftwareRequest $request, array $cast, string $type): void
    {
        $fresh = $this->createRequest($type, $cast['pemohon']);

        $checks = [
            [
                'Pemohon menyetujui pengajuannya sendiri',
                $cast['pemohon'], 'approve', $fresh, false,
            ],
            [
                'Head departemen LAIN menyetujui',
                $cast['head_lain'], 'approve', $fresh, false,
            ],
            [
                'Head of IT menyetujui padahal masih tahap Kepala Departemen',
                $cast['it_head'], 'approve', $fresh, false,
            ],
            [
                'IT Admin menyetujui padahal masih tahap Kepala Departemen',
                $cast['it'], 'approve', $fresh, false,
            ],
            [
                'Head departemen sendiri menyetujui (seharusnya boleh)',
                $cast['head'], 'approve', $fresh, true,
            ],
        ];

        $rows = [];

        foreach ($checks as [$label, $actor, $ability, $target, $expected]) {
            $actual = Gate::forUser($actor)->allows($ability, $target);
            $ok = $actual === $expected;

            $rows[] = [
                $label,
                $expected ? 'boleh' : 'ditolak',
                $actual ? 'boleh' : 'ditolak',
                $ok ? 'sesuai' : 'TIDAK SESUAI',
            ];

            if (! $ok) {
                $this->findings[] = "Pengaman gagal: {$label}.";
            }
        }

        // Pengajuan yang sudah final tidak boleh disentuh lagi.
        $done = $this->createRequest($type, $cast['pemohon']);
        $done->status = RequestStatus::Completed->value;
        $done->save();

        foreach (['approve', 'reject'] as $ability) {
            $actual = Gate::forUser($cast['it'])->allows($ability, $done);
            $rows[] = [
                "Pengajuan sudah Selesai lalu di-{$ability}",
                'ditolak',
                $actual ? 'boleh' : 'ditolak',
                $actual ? 'TIDAK SESUAI' : 'sesuai',
            ];

            if ($actual) {
                $this->findings[] = "Pengaman gagal: pengajuan yang sudah Selesai masih bisa di-{$ability}.";
            }
        }

        // Pihak yang MENYERAHKAN tidak boleh menandatangani atas nama
        // penerimanya - ini pengaman paling penting di tahap serah terima,
        // karena kalau jebol, BAST-nya kehilangan seluruh makna sebagai bukti.
        $awaitingBast = $this->createRequest($type, $cast['pemohon']);
        $awaitingBast->status = RequestStatus::WaitingBastSignature->value;
        $awaitingBast->save();

        $itAdminCanSign = Gate::forUser($cast['it'])->allows('signBast', $awaitingBast);
        $rows[] = [
            'IT Admin menandatangani BAST atas nama pemohon',
            'ditolak',
            $itAdminCanSign ? 'boleh' : 'ditolak',
            $itAdminCanSign ? 'TIDAK SESUAI' : 'sesuai',
        ];

        if ($itAdminCanSign) {
            $this->findings[] = 'Pengaman gagal: IT Admin bisa menandatangani BAST atas nama pemohon.';
        }

        // ON_EXTERNAL_PROCESS itu pencatatan administratif, bukan keputusan -
        // jadi tidak boleh ada penolakan di sana.
        $external = $this->createRequest($type, $cast['pemohon']);
        $external->status = RequestStatus::OnExternalProcess->value;
        $external->save();

        $canReject = Gate::forUser($cast['it'])->allows('reject', $external);
        $rows[] = [
            'IT Admin menolak di tahap Diproses Eksternal',
            'ditolak',
            $canReject ? 'boleh' : 'ditolak',
            $canReject ? 'TIDAK SESUAI' : 'sesuai',
        ];

        if ($canReject) {
            $this->findings[] = 'Pengaman gagal: tahap Diproses Eksternal masih bisa ditolak.';
        }

        // Pengajuan dari orang IT sendiri harus langsung mendarat di tahap
        // Head of IT, bukan di tahap kepala departemen - kalau tidak, Head of
        // IT diminta menyetujui dua kali untuk satu dokumen yang sama.
        $fromIt = $this->createRequest($type, $cast['pemohon_it']);
        $startedAtItHead = $fromIt->status === RequestStatus::WaitingHeadItApproval->value;

        $rows[] = [
            'Pengajuan dari orang IT langsung ke tahap Head of IT',
            'ya',
            RequestStatus::from($fromIt->status)->label(),
            $startedAtItHead ? 'sesuai' : 'TIDAK SESUAI',
        ];

        if (! $startedAtItHead) {
            $this->findings[] = 'Pengajuan dari departemen IT tidak langsung masuk ke tahap Head of IT.';
        }

        // Kasus khusus: kepala departemen mengajukan untuk dirinya sendiri.
        // Di kode sekarang ini LOLOS, dan itu memang temuan yang perlu
        // diputuskan - bukan bug simulasi.
        $ownRequest = $this->createRequest($type, $cast['head']);
        $selfApprove = Gate::forUser($cast['head'])->allows('approve', $ownRequest);

        $rows[] = [
            'Kepala departemen menyetujui pengajuannya SENDIRI',
            'idealnya ditolak',
            $selfApprove ? 'boleh' : 'ditolak',
            $selfApprove ? 'PERLU DIPUTUSKAN' : 'sesuai',
        ];

        if ($selfApprove) {
            $this->findings[] = 'Kepala departemen masih bisa menyetujui pengajuannya sendiri - belum ada aturan yang mencegahnya.';
        }

        $this->newLine();
        $this->components->info('Cek pengaman otorisasi');
        $this->table(['Skenario', 'Harusnya', 'Kenyataannya', 'Kesimpulan'], $rows);
    }

    private function printFindings(): void
    {
        $this->newLine();

        if ($this->findings === []) {
            $this->components->info('Tidak ada temuan. Semua tahap dan pengaman berperilaku sesuai aturan.');

            return;
        }

        $this->components->warn('Temuan:');

        foreach ($this->findings as $finding) {
            $this->line('  - '.$finding);
        }
    }

    /**
     * Memajukan satu tahap, meniru gabungan ApprovalController::handleApprove()
     * dan FulfillmentController - termasuk data yang wajib terisi di tahap
     * serah terima, supaya simulasinya tidak menghasilkan BAST tanpa nomor seri
     * yang di aplikasi asli tidak mungkin terjadi.
     */
    private function applyAdvance(HardwareRequest|SoftwareRequest $request, User $actor): void
    {
        $from = RequestStatus::from($request->status);

        if ($from === RequestStatus::ItemOnTheWay) {
            $request->item_identifier = 'SN-SIMULASI-'.$request->id;
            $request->handed_over_at = now();
            $request->handed_over_by_id = $actor->id;
        }

        if ($from === RequestStatus::WaitingBastSignature) {
            $request->bast_signed_at = now();
        }

        $request->status = $from->next()->value;
        $request->save();
        $request->recordEvent($request->status, $actor, $from->value);
    }

    private function applyReject(HardwareRequest|SoftwareRequest $request, User $actor): void
    {
        // Persis seperti ApprovalController::handleReject().
        $from = $request->status;
        $request->rejected_at_stage = $from;
        $request->rejected_by_id = $actor->id;
        $request->rejected_at = now();
        $request->status = RequestStatus::Rejected->value;
        $request->save();
        $request->recordEvent($request->status, $actor, $from);
    }

    /**
     * @return array<string, User>
     */
    private function buildCast(): array
    {
        $suffix = Str::lower(Str::random(6));

        $deptIt = $this->makeDepartement('IT (simulasi)');
        $deptOps = $this->makeDepartement('Operations (simulasi)');
        $deptHr = $this->makeDepartement('HR (simulasi)');

        // Departemen IT sengaja TIDAK diberi kepala ber-role 'head' - kepalanya
        // memang Head of IT itu sendiri. Keadaan inilah yang dibaca ApprovalChain
        // buat memotong satu tahap bagi pengajuan yang datang dari orang IT.
        return [
            'pemohon' => $this->makeUser('Pemohon', 'user', $deptOps, $suffix),
            'head' => $this->makeUser('Kepala Departemen', 'head', $deptOps, $suffix),
            'head_lain' => $this->makeUser('Kepala Departemen Lain', 'head', $deptHr, $suffix),
            'it_head' => $this->makeUser('Head of IT', 'it_head', $deptIt, $suffix),
            'it' => $this->makeUser('IT Admin', 'it', $deptIt, $suffix),
            'pemohon_it' => $this->makeUser('Pemohon dari IT', 'user', $deptIt, $suffix),
        ];
    }

    private function makeDepartement(string $name): Departement
    {
        $departement = new Departement();
        $departement->name = $name;
        $departement->save();

        return $departement;
    }

    private function makeUser(string $label, string $role, Departement $departement, string $suffix): User
    {
        $user = new User();
        $user->name = $label.' (simulasi)';
        $user->email = Str::slug($label).'-'.$suffix.'@simulasi.test';
        $user->password = Hash::make(Str::random(32));
        $user->entity = 'PT VISINEMA PICTURES';
        $user->position = $label;
        $user->departement_id = $departement->id;
        $user->role = $role;
        $user->can_manage_users = false;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        // Sejak menyetujui menuntut tanda tangan digital, pemeran simulasi
        // dianggap sudah punya - yang diuji di sini alur & otorisasinya,
        // bukan pembuatan tanda tangannya (itu ada di SignatureTest).
        $user->forceFill([
            'signature_path' => 'signatures/simulasi-'.$user->id.'.sig',
            'signature_uploaded_at' => now(),
        ])->save();

        return $user->load('departement');
    }

    private function createRequest(string $type, User $pemohon): HardwareRequest|SoftwareRequest
    {
        if ($type === 'hardware') {
            $request = new HardwareRequest();
            $request->user_id = $pemohon->id;
            $request->request_date = now()->toDateString();
            $request->hardware_type = 'laptop';
            $request->hardware_recommendation = 'MacBook Pro 14 M3 (simulasi)';
            $request->justification = 'Data simulasi alur approval.';
        } else {
            $request = new SoftwareRequest();
            $request->user_id = $pemohon->id;
            $request->request_date = now()->toDateString();
            $request->software_name = 'Adobe Creative Cloud (simulasi)';
            $request->software_type = 'Subscription';
            $request->software_usage = 'individu';
            $request->license_count = 1;
            $request->duration_months = 12;
            $request->estimated_cost = 5000000;
            $request->justification = 'Data simulasi alur approval.';
        }

        $request->digital_signature = true;
        // Lewat ApprovalChain, sama seperti RequestController - supaya simulasi
        // ini ikut menguji pemotongan tahap buat pemohon dari departemen IT.
        $request->status = app(ApprovalChain::class)->startingStatusFor($pemohon)->value;
        $request->save();

        return $request->load('user');
    }
}

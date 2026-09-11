<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\HardwareRequest;
use App\Models\RequestEvent;
use App\Models\SoftwareRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman detail satu pengajuan: ringkasan, lampiran, linimasa, dan dokumen.
 *
 * Halaman ini sengaja punya alamat sendiri (bukan modal di dashboard) karena
 * inilah yang jadi rujukan kalau ada yang dipertanyakan - tautannya bisa
 * dikirim, dibuka ulang, dan ditunjukkan apa adanya.
 */
class RequestDetailController extends Controller
{
    public function show(string $type, int $id): Response
    {
        $request = $this->findRequest($type, $id);

        // Siapa yang boleh melihat diatur RequestPolicy::view - pemohon,
        // kepala departemennya, Head of IT, dan IT Admin.
        $this->authorize('view', $request);

        return Inertia::render('Requests/Show', [
            'request' => $this->presentRequest($type, $request),
            'timeline' => $this->presentTimeline($request),
            'abilities' => $this->abilitiesFor($request),
        ]);
    }

    /**
     * Apa yang boleh dilakukan user yang sedang melihat, dihitung di server.
     *
     * Frontend cuma memakainya buat menampilkan atau menyembunyikan tombol.
     * Otorisasi sebenarnya tetap dicek ulang di FulfillmentController - kalau
     * tidak, siapapun yang menembak POST langsung tetap tembus.
     */
    private function abilitiesFor(HardwareRequest|SoftwareRequest $request): array
    {
        $status = RequestStatus::tryFrom($request->status);

        return [
            'markOnTheWay' => $status === RequestStatus::OnExternalProcess
                && Gate::allows('approve', $request),
            'markHandedOver' => $status === RequestStatus::ItemOnTheWay
                && Gate::allows('approve', $request),
            'signBast' => Gate::allows('signBast', $request),
            // Dipakai buat menjelaskan ke pemohon kenapa tombol tanda
            // tangannya belum bisa dipakai.
            'hasSignature' => (bool) Auth::user()->has_signature,
        ];
    }

    private function presentRequest(string $type, HardwareRequest|SoftwareRequest $request): array
    {
        $shared = [
            'id' => $request->id,
            'type' => $type === 'hardware' ? 'Hardware' : 'Software',
            'type_key' => $type,
            'status' => $request->status,
            'request_date' => $request->request_date,
            'justification' => $request->justification,
            'requester_name' => $request->user?->name,
            'requester_position' => $request->user?->position,
            'requester_entity' => $request->user?->entity,
            'requester_department' => $request->user?->departement?->name,
            'preference_image_url' => $request->preference_image_path
                ? route("request.{$type}.preference-image", $request->id)
                : null,
            'item_identifier' => $request->item_identifier,
            'handed_over_at' => $request->handed_over_at?->toIso8601String(),
            'handed_over_by_name' => $request->handedOverBy?->name,
            'bast_signed_at' => $request->bast_signed_at?->toIso8601String(),
            'rejected_by_name' => $request->rejectedBy?->name,
            'rejected_at' => $request->rejected_at?->toIso8601String(),
        ];

        if ($request instanceof HardwareRequest) {
            return $shared + [
                'title' => $request->custom_hardware_name ?: $request->hardware_recommendation ?: $request->hardware_type,
                // Pasangan label-nilai yang ditampilkan di kotak Ringkasan.
                // Bentuknya disamakan buat hardware & software supaya
                // halamannya tidak perlu tahu ini pengajuan jenis apa.
                'fields' => [
                    ['label' => 'Jenis Perangkat', 'value' => $request->hardware_type],
                    ['label' => 'Spesifikasi Diminta', 'value' => $request->custom_hardware_name ?: $request->hardware_recommendation],
                    ['label' => 'Di Luar Standar Perusahaan', 'value' => $request->hardware_recommendation === 'custom' ? 'Ya' : 'Tidak'],
                ],
            ];
        }

        return $shared + [
            'title' => $request->software_name,
            'fields' => [
                ['label' => 'Jenis Lisensi', 'value' => $request->software_type],
                ['label' => 'Dipakai Untuk', 'value' => $request->software_usage === 'team' ? 'Tim' : 'Individu'],
                ['label' => 'Jumlah Lisensi', 'value' => (string) $request->license_count],
                ['label' => 'Durasi', 'value' => $request->duration_months ? $request->duration_months.' bulan' : 'Tanpa batas waktu'],
                ['label' => 'Perkiraan Biaya', 'value' => 'Rp '.number_format((float) $request->estimated_cost, 0, ',', '.')],
            ],
        ];
    }

    private function presentTimeline(HardwareRequest|SoftwareRequest $request): array
    {
        return $request->events()->with('actor')->get()
            ->map(fn (RequestEvent $event) => [
                'id' => $event->id,
                'title' => $event->title(),
                'actor_name' => $event->actor?->name,
                'note' => $event->note,
                'to_status' => $event->to_status,
                'created_at' => $event->created_at?->toIso8601String(),
            ])
            ->all();
    }

    private function findRequest(string $type, int $id): HardwareRequest|SoftwareRequest
    {
        $model = match ($type) {
            'hardware' => HardwareRequest::class,
            'software' => SoftwareRequest::class,
            default => abort(404),
        };

        return $model::with(['user.departement', 'rejectedBy', 'handedOverBy'])->findOrFail($id);
    }
}

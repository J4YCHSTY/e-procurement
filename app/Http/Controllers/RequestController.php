<?php

namespace App\Http\Controllers;

use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use App\Support\Request\ApprovalChain;
use App\Support\Request\PreferenceImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class RequestController extends Controller
{
    public function __construct(
        private readonly PreferenceImageStorage $preferenceImages,
        private readonly ApprovalChain $approvalChain,
    ) {}

    public function storeHardware(Request $request): RedirectResponse
    {
        if ($blocked = $this->rejectIfNoSignature()) {
            return $blocked;
        }

        $validatedData = $request->validate([
            'request_date' => 'required|date',
            'hardware_type' => 'required|string|max:255',
            'hardware_recommendation' => 'nullable|string|max:255',
            // Wajib diisi kalau perangkatnya di luar standar: tanpa ini yang
            // tercatat cuma kata 'custom', dan procurement tidak punya nama
            // barang untuk dicari quotation-nya.
            'custom_hardware_name' => 'nullable|string|max:255|required_if:hardware_recommendation,custom',
            // Wajib kalau pemohon memilih perangkat di luar standar perusahaan:
            // di kasus itu tidak ada spesifikasi baku yang bisa dijadikan acuan,
            // jadi gambarnya yang jadi acuan buat procurement.
            'preference_image' => 'nullable|image|mimes:png,jpg,jpeg|max:5120|required_if:hardware_recommendation,custom',
            'justification' => 'required|string',
            'digital_signature' => 'required|boolean',
        ], [
            'custom_hardware_name.required_if' => 'Tulis nama perangkat yang kamu ajukan.',
            'preference_image.required_if' => 'Untuk perangkat di luar standar, lampirkan tangkapan layar barang yang kamu maksud.',
            'preference_image.image' => 'Lampiran preferensi harus berupa gambar.',
            'preference_image.max' => 'Ukuran gambar preferensi maksimal 5 MB.',
        ]);

        try {
            $preferencePath = $request->hasFile('preference_image')
                ? $this->preferenceImages->store($request->file('preference_image'))
                : null;
        } catch (RuntimeException $e) {
            return back()->withErrors(['preference_image' => $e->getMessage()])->withInput();
        }

        $hardwareRequest = new HardwareRequest();
        $hardwareRequest->user_id = Auth::id();
        $hardwareRequest->request_date = $validatedData['request_date'];
        $hardwareRequest->hardware_type = $validatedData['hardware_type'];
        $hardwareRequest->hardware_recommendation = $validatedData['hardware_recommendation'];
        $hardwareRequest->custom_hardware_name = $validatedData['custom_hardware_name'] ?? null;
        $hardwareRequest->preference_image_path = $preferencePath;
        $hardwareRequest->justification = $validatedData['justification'];
        $hardwareRequest->digital_signature = $validatedData['digital_signature'];
        // Tahap awalnya nggak selalu kepala departemen - lihat ApprovalChain.
        $hardwareRequest->status = $this->approvalChain->startingStatusFor(Auth::user())->value;
        $hardwareRequest->save();

        // Baris pertama linimasa. Dicatat di sini, bukan lewat event model,
        // supaya jelas terbaca bahwa pembuatan pengajuan pun adalah satu
        // kejadian yang punya pelaku dan waktu - sama seperti tahap lainnya.
        $hardwareRequest->recordEvent($hardwareRequest->status, Auth::user(), null);

        return redirect()->back()->with('success', 'Form Request Hardware Berhasil Dikirim.');
    }

    public function storeSoftware(Request $request): RedirectResponse
    {
        if ($blocked = $this->rejectIfNoSignature()) {
            return $blocked;
        }

        $validateData = $request->validate([
            'request_date' => 'required|date',
            'software_name' => 'required|string|max:255',
            'software_type' => 'required|string|max:255',
            'software_usage' => 'required|in:individu,team',
            'license_count' => 'required|integer|min:1',
            'duration_months' => 'nullable|integer|min:1|required_unless:software_type,License',
            'estimated_cost' => 'required|numeric|min:0',
            'preference_image' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
            'justification' => 'required|string',
            'digital_signature' => 'required|boolean',
        ], [
            'preference_image.image' => 'Lampiran preferensi harus berupa gambar.',
            'preference_image.max' => 'Ukuran gambar preferensi maksimal 5 MB.',
        ]);

        try {
            $preferencePath = $request->hasFile('preference_image')
                ? $this->preferenceImages->store($request->file('preference_image'))
                : null;
        } catch (RuntimeException $e) {
            return back()->withErrors(['preference_image' => $e->getMessage()])->withInput();
        }

        $softwareRequest = new SoftwareRequest();
        $softwareRequest->user_id = Auth::id();
        $softwareRequest->request_date = $validateData['request_date'];
        $softwareRequest->software_name = $validateData['software_name'];
        $softwareRequest->software_type = $validateData['software_type'];
        $softwareRequest->software_usage = $validateData['software_usage'];
        $softwareRequest->license_count = $validateData['license_count'];
        $softwareRequest->duration_months = $validateData['duration_months'];
        $softwareRequest->estimated_cost = $validateData['estimated_cost'];
        $softwareRequest->preference_image_path = $preferencePath;
        $softwareRequest->justification = $validateData['justification'];
        $softwareRequest->digital_signature = $validateData['digital_signature'];
        $softwareRequest->status = $this->approvalChain->startingStatusFor(Auth::user())->value;
        $softwareRequest->save();

        $softwareRequest->recordEvent($softwareRequest->status, Auth::user(), null);

        return redirect()->back()->with('success', 'Form Request Software Berhasil Dikirim.');
    }

    public function hardwarePreferenceImage(HardwareRequest $hardwareRequest): Response
    {
        return $this->streamPreferenceImage($hardwareRequest);
    }

    public function softwarePreferenceImage(SoftwareRequest $softwareRequest): Response
    {
        return $this->streamPreferenceImage($softwareRequest);
    }

    /**
     * Tanda tangan dicek di server juga, bukan cuma disembunyikan tombolnya
     * di layar - kalau tidak, request yang ditembak langsung tetap tembus
     * dan menghasilkan pengajuan yang formnya nanti tidak bisa ditandatangani.
     */
    private function rejectIfNoSignature(): ?RedirectResponse
    {
        if (Auth::user()->has_signature) {
            return null;
        }

        return back()->withErrors([
            'digital_signature' => 'Kamu belum punya tanda tangan digital. Buat dulu di form ini (cukup sekali), baru pengajuannya bisa dikirim.',
        ])->withInput();
    }

    private function streamPreferenceImage(HardwareRequest|SoftwareRequest $request): Response
    {
        $this->authorize('view', $request);

        $image = $this->preferenceImages->get($request->preference_image_path);

        abort_if($image === null, 404);

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => (string) strlen($image),
            'Content-Disposition' => 'inline; filename="preferensi-barang.png"',
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

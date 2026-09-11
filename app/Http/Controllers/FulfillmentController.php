<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Tahap sesudah semua persetujuan: perjalanan barangnya sampai diterima
 * pemohon.
 *
 * Dipisah dari ApprovalController karena isinya beda jenis. Di sana yang
 * terjadi adalah KEPUTUSAN (setuju / tolak) yang bisa menghentikan alur;
 * di sini yang terjadi cuma PENCATATAN kejadian yang sudah terlanjur berjalan
 * di dunia nyata - PO terbit, barang datang, barang diserahkan. Mencampur
 * keduanya bikin satu kelas yang aturannya bercabang-cabang tanpa alasan.
 */
class FulfillmentController extends Controller
{
    public function markOnTheWay(HttpRequest $http, string $type, int $id): RedirectResponse
    {
        $request = $this->findRequest($type, $id);

        $this->authorize('approve', $request);

        if ($request->status !== RequestStatus::OnExternalProcess->value) {
            return back()->with('error', 'Pengajuan ini sudah tidak di tahap Diproses Eksternal.');
        }

        $validated = $http->validate([
            'note' => 'nullable|string|max:500',
        ], [
            'note.max' => 'Catatannya maksimal 500 karakter.',
        ]);

        $from = $request->status;
        $request->status = RequestStatus::ItemOnTheWay->value;
        $request->save();

        $request->recordEvent(
            $request->status,
            Auth::user(),
            $from,
            $validated['note'] ?? null,
        );

        return back()->with('success', 'Status diperbarui: barang dalam perjalanan.');
    }

    /**
     * Barang sampai dan diserahkan ke pemohon. Di titik inilah BAST terbit,
     * lalu bolanya pindah ke pemohon untuk menandatangani.
     */
    public function markHandedOver(HttpRequest $http, string $type, int $id): RedirectResponse
    {
        $request = $this->findRequest($type, $id);

        $this->authorize('approve', $request);

        if ($request->status !== RequestStatus::ItemOnTheWay->value) {
            return back()->with('error', 'Pengajuan ini sudah tidak di tahap Barang Dalam Perjalanan.');
        }

        $validated = $http->validate([
            // Wajib: tanpa ini BAST tidak bisa membuktikan unit MANA yang
            // diserahkan, dan itu justru hal pertama yang dicari kalau nanti
            // ada perselisihan soal aset.
            'item_identifier' => 'required|string|max:255',
            'note' => 'nullable|string|max:500',
        ], [
            'item_identifier.required' => 'Isi nomor seri barangnya (untuk software: kunci lisensinya).',
            'note.max' => 'Catatannya maksimal 500 karakter.',
        ]);

        $from = $request->status;
        $request->item_identifier = $validated['item_identifier'];
        $request->handed_over_at = now();
        $request->handed_over_by_id = Auth::id();
        $request->status = RequestStatus::WaitingBastSignature->value;
        $request->save();

        $request->recordEvent(
            $request->status,
            Auth::user(),
            $from,
            $validated['note'] ?? null,
        );

        return back()->with('success', 'BAST diterbitkan dan menunggu tanda tangan pemohon.');
    }

    /**
     * Pemohon menandatangani BAST. Ini yang menutup alurnya - bukan IT.
     */
    public function signBast(string $type, int $id): RedirectResponse
    {
        $request = $this->findRequest($type, $id);

        $this->authorize('signBast', $request);

        // Tanda tangannya ikut menempel di BAST yang dicetak, jadi harus sudah
        // ada dulu - sama seperti syarat di sisi pengajuan dan persetujuan.
        if (! Auth::user()->has_signature) {
            return back()->with(
                'error',
                'Kamu belum punya tanda tangan digital. Buat dulu lewat menu Profil Saya, baru BAST-nya bisa ditandatangani.'
            );
        }

        $from = $request->status;
        $request->bast_signed_at = now();
        $request->status = RequestStatus::Completed->value;
        $request->save();

        $request->recordEvent($request->status, Auth::user(), $from);

        return back()->with('success', 'BAST sudah kamu tandatangani. Pengajuan ini selesai.');
    }

    /**
     * Satu route untuk hardware & software, dibedakan lewat satu segmen URL.
     *
     * Alternatifnya adalah menggandakan tiga route jadi enam seperti pola lama
     * di ApprovalController - dan setiap tahap baru berarti dua route lagi.
     * Nilai selain 'hardware'/'software' langsung 404, jadi segmen ini tidak
     * bisa dipakai menebak-nebak tabel lain.
     */
    private function findRequest(string $type, int $id): HardwareRequest|SoftwareRequest
    {
        $model = match ($type) {
            'hardware' => HardwareRequest::class,
            'software' => SoftwareRequest::class,
            default => abort(404),
        };

        return $model::with('user')->findOrFail($id);
    }
}

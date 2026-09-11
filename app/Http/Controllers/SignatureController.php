<?php

namespace App\Http\Controllers;

use App\Support\Signature\SignatureStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * Tanda tangan digital milik user yang sedang login.
 *
 * Perhatikan: TIDAK ADA satu pun method di sini yang menerima ID user.
 * Itu disengaja. Selama tidak ada endpoint yang bisa diminta "tanda tangan
 * si A", tidak ada yang bisa ditebak atau disalahgunakan - pengamanannya
 * bukan dari pengecekan izin, tapi karena kemampuannya memang tidak ada.
 *
 * Tanda tangan orang lain hanya pernah muncul dalam keadaan sudah menyatu
 * di dalam PDF yang dirender di server, tidak pernah sebagai berkas gambar
 * yang bisa diambil terpisah.
 */
class SignatureController extends Controller
{
    public function __construct(private readonly SignatureStorage $storage) {}

    /**
     * Tampilkan tanda tangan milik sendiri sebagai PNG.
     */
    public function show(Request $request): Response
    {
        $png = $this->storage->get($request->user());

        abort_if($png === null, 404);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => (string) strlen($png),
            'Content-Disposition' => 'inline; filename="tanda-tangan.png"',
            // Jangan sampai nyangkut di cache browser atau proxy.
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Simpan tanda tangan baru (menimpa yang lama kalau sudah ada).
     *
     * Frontend selalu mengirim PNG dalam bentuk data URL - baik hasil
     * menggambar di kanvas maupun hasil unggah berkas yang sudah diproses
     * di browser. Satu bentuk masukan berarti satu jalur validasi di sini.
     */
    public function store(Request $request): RedirectResponse
    {
        $prefix = 'data:image/png;base64,';

        $validated = $request->validate([
            'signature' => ['required', 'string', 'starts_with:'.$prefix, 'max:2500000'],
        ], [
            'signature.required' => 'Tanda tangan belum diisi.',
            'signature.starts_with' => 'Format tanda tangan tidak dikenali.',
            'signature.max' => 'Ukuran gambar tanda tangan terlalu besar.',
        ]);

        $binary = base64_decode(substr($validated['signature'], strlen($prefix)), true);

        if ($binary === false || $binary === '') {
            return back()->withErrors(['signature' => 'Data tanda tangan tidak valid.']);
        }

        try {
            $this->storage->store($request->user(), $binary);
        } catch (RuntimeException $e) {
            return back()->withErrors(['signature' => $e->getMessage()]);
        }

        return back()->with('success', 'Tanda tangan digital berhasil disimpan.');
    }

    /**
     * Hapus tanda tangan sendiri.
     *
     * Dokumen yang sudah terlanjur dibuat tidak ikut berubah, karena PDF-nya
     * dibekukan saat digenerate - bukan dirender ulang tiap kali dibuka.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->storage->forget($request->user());

        return back()->with('success', 'Tanda tangan digital sudah dihapus.');
    }
}

import DangerButton from '@/Components/DangerButton';
import { IconCheckCircle, IconPencil } from '@/Components/Icons';
import SignaturePad from '@/Components/SignaturePad';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function formatDate(value) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function UpdateSignatureForm({ className = '' }) {
    const user = usePage().props.auth.user;

    // Dipakai buat memaksa <img> mengambil ulang gambarnya setelah tanda
    // tangan diganti. Tanpa ini browser menampilkan versi lama dari cache.
    const [version, setVersion] = useState(() => Date.now());
    const [removing, setRemoving] = useState(false);

    const hasSignature = user.has_signature;
    const uploadedAt = formatDate(user.signature_uploaded_at);

    const remove = () => {
        setRemoving(true);
        router.delete(route('signature.destroy'), {
            preserveScroll: true,
            onSuccess: () => setVersion(Date.now()),
            onFinish: () => setRemoving(false),
        });
    };

    return (
        <section className={className}>
            <header className="flex items-center gap-2.5">
                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-accent-soft text-accent-deep">
                    <IconPencil className="h-4 w-4" />
                </span>
                <div>
                    <h2 className="text-[15.5px] font-extrabold text-ink">
                        Tanda Tangan Digital
                    </h2>
                    <p className="mt-0.5 text-[12.5px] text-ink-muted">
                        Dipakai otomatis di form pengajuan dan BAST. Cukup diisi
                        sekali &mdash; setelah ini kamu tinggal klik saja.
                    </p>
                </div>
            </header>

            {hasSignature && (
                <div className="mt-5 flex flex-wrap items-center gap-4 rounded-[16px] border border-line bg-surface-sunken p-4">
                    <div
                        className="flex h-[110px] w-[220px] shrink-0 items-center justify-center overflow-hidden rounded-xl border border-line bg-surface"
                        style={{
                            backgroundImage:
                                'linear-gradient(45deg, var(--color-surface-sunken) 25%, transparent 25%), linear-gradient(-45deg, var(--color-surface-sunken) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, var(--color-surface-sunken) 75%), linear-gradient(-45deg, transparent 75%, var(--color-surface-sunken) 75%)',
                            backgroundSize: '16px 16px',
                            backgroundPosition: '0 0, 0 8px, 8px -8px, -8px 0',
                        }}
                    >
                        <img
                            src={`${route('signature.show')}?v=${version}`}
                            alt="Tanda tangan digital kamu"
                            className="max-h-[94px] max-w-[200px] object-contain"
                        />
                    </div>

                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-1.5 text-[13px] font-bold text-success">
                            <IconCheckCircle className="h-4 w-4" />
                            Tanda tangan sudah terpasang
                        </div>
                        {uploadedAt && (
                            <p className="mt-1 text-[12px] text-ink-muted">
                                Disimpan {uploadedAt}
                            </p>
                        )}
                        <p className="mt-2 text-[11.5px] leading-relaxed text-ink-faint">
                            Menggantinya tidak mengubah dokumen yang sudah
                            terlanjur dibuat &mdash; berkas lama dibekukan saat
                            digenerate.
                        </p>

                        <div className="mt-3">
                            <DangerButton
                                onClick={remove}
                                disabled={removing}
                                className="!px-3.5 !py-2 !text-xs"
                            >
                                {removing ? 'Menghapus...' : 'Hapus tanda tangan'}
                            </DangerButton>
                        </div>
                    </div>
                </div>
            )}

            <div className="mt-5">
                <h3 className="text-[13px] font-bold text-ink">
                    {hasSignature ? 'Ganti tanda tangan' : 'Buat tanda tangan'}
                </h3>
                <p className="mb-3 mt-0.5 text-[12px] text-ink-muted">
                    Gambar langsung di kotak di bawah, atau unggah hasil scan /
                    foto tanda tangan kamu.
                </p>

                <SignaturePad
                    onSaved={() => setVersion(Date.now())}
                    submitLabel={
                        hasSignature ? 'Simpan Tanda Tangan Baru' : 'Simpan Tanda Tangan'
                    }
                />
            </div>

            <div className="mt-5 rounded-[14px] bg-surface-sunken px-4 py-3.5 text-[11.5px] leading-relaxed text-ink-muted">
                Gambar tanda tangan disimpan <strong className="text-ink">terenkripsi</strong> di
                direktori privat server, di luar jangkauan browser. Tidak ada
                halaman yang bisa menampilkan tanda tangan orang lain &mdash;
                punyamu hanya muncul di sini dan menyatu di dalam PDF yang
                dibuat server.
            </div>
        </section>
    );
}

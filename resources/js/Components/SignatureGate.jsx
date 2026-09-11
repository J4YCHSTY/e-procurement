import { IconCheckCircle, IconPencil } from '@/Components/Icons';
import SignaturePad from '@/Components/SignaturePad';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Gerbang tanda tangan digital di depan sebuah aksi (kirim pengajuan,
 * setujui, dsb).
 *
 * Kalau akunnya belum punya tanda tangan, kotak pembuatnya muncul di sini dan
 * tombol aksinya ditahan sampai tersimpan. Kalau sudah punya, yang tampil cuma
 * baris konfirmasi tipis - tidak ada langkah tambahan sama sekali. Jadi
 * pengisiannya betul-betul sekali seumur akun, persis di titik di mana orang
 * baru sadar dia membutuhkannya.
 */
export default function SignatureGate({ className = '' }) {
    const user = usePage().props.auth.user;
    const [version, setVersion] = useState(() => Date.now());

    if (user.has_signature) {
        return (
            <div
                className={
                    'flex flex-wrap items-center gap-3 rounded-[14px] border border-line bg-surface-sunken px-4 py-3 ' +
                    className
                }
            >
                <IconCheckCircle className="h-[17px] w-[17px] shrink-0 text-success" />
                <span className="text-[12.5px] font-semibold text-ink-muted">
                    Tanda tangan digital kamu akan otomatis dilampirkan.
                </span>
                <span
                    className="ms-auto flex h-9 w-[92px] items-center justify-center overflow-hidden rounded-lg border border-line bg-surface"
                    title="Tanda tangan digital kamu"
                >
                    <img
                        src={`${route('signature.show')}?v=${version}`}
                        alt="Tanda tangan kamu"
                        className="max-h-8 max-w-[84px] object-contain"
                    />
                </span>
            </div>
        );
    }

    return (
        <div
            className={
                'rounded-[16px] border-[1.5px] border-warning bg-warning-soft p-4 ' + className
            }
        >
            <div className="flex items-start gap-2.5">
                <IconPencil className="mt-0.5 h-[17px] w-[17px] shrink-0 text-warning" />
                <div>
                    <p className="text-[13px] font-extrabold text-warning">
                        Buat tanda tangan digital dulu
                    </p>
                    <p className="mt-0.5 text-[12px] leading-relaxed text-warning">
                        Form pengajuan yang dicetak nanti membutuhkan tanda
                        tangan kamu. Cukup dibuat sekali &mdash; pengajuan
                        berikutnya tidak akan diminta lagi.
                    </p>
                </div>
            </div>

            <div className="mt-3.5 rounded-[14px] bg-surface p-3.5">
                <SignaturePad
                    onSaved={() => setVersion(Date.now())}
                    submitLabel="Simpan Tanda Tangan"
                />
            </div>
        </div>
    );
}

import { IconImage, IconTrash, IconUpload } from '@/Components/Icons';
import InputError from '@/Components/InputError';
import SecondaryButton from '@/Components/SecondaryButton';
import { useEffect, useRef, useState } from 'react';

/**
 * Lampiran preferensi barang: tangkapan layar produk dari marketplace.
 *
 * Sengaja gambar, bukan kolom tautan. Tautan marketplace gampang mati begitu
 * produknya dihapus penjual, sedangkan tangkapan layarnya tetap terbaca waktu
 * berkasnya dibuka lagi berbulan-bulan kemudian.
 */
export default function PreferenceImageField({
    value,
    onChange,
    error,
    required = false,
    hint,
}) {
    const inputRef = useRef(null);
    const [preview, setPreview] = useState(null);

    useEffect(() => {
        if (!value) {
            setPreview(null);

            return undefined;
        }

        const url = URL.createObjectURL(value);
        setPreview(url);

        return () => URL.revokeObjectURL(url);
    }, [value]);

    const pick = (event) => {
        const file = event.target.files?.[0] ?? null;
        event.target.value = '';
        onChange(file);
    };

    return (
        <div>
            <label className="form-label">
                Preferensi Barang{' '}
                {required ? (
                    <span className="text-danger">*</span>
                ) : (
                    <span className="font-medium text-ink-faint">(opsional)</span>
                )}
            </label>

            <p className="mb-2.5 text-xs leading-relaxed text-ink-muted">
                {hint ??
                    'Tangkapan layar barang dari marketplace, supaya tim IT dan procurement tahu persis yang kamu maksud. Gambar disimpan di sistem, jadi tetap terbaca walau produknya sudah dihapus penjual.'}
            </p>

            {preview ? (
                <div className="flex flex-wrap items-center gap-3.5 rounded-[14px] border border-line bg-surface-sunken p-3">
                    <img
                        src={preview}
                        alt="Pratinjau preferensi barang"
                        className="h-[86px] w-[120px] shrink-0 rounded-lg border border-line bg-surface object-contain"
                    />
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-[12.5px] font-semibold text-ink">
                            {value.name}
                        </p>
                        <p className="mt-0.5 text-[11.5px] text-ink-muted">
                            {(value.size / 1024).toFixed(0)} KB
                        </p>
                        <div className="mt-2 flex gap-2">
                            <SecondaryButton
                                onClick={() => inputRef.current?.click()}
                                className="!px-3 !py-1.5 !text-[11.5px]"
                            >
                                <IconUpload className="h-3.5 w-3.5" />
                                Ganti
                            </SecondaryButton>
                            <SecondaryButton
                                onClick={() => onChange(null)}
                                className="!px-3 !py-1.5 !text-[11.5px]"
                            >
                                <IconTrash className="h-3.5 w-3.5" />
                                Hapus
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            ) : (
                <button
                    type="button"
                    onClick={() => inputRef.current?.click()}
                    className="flex w-full flex-col items-center justify-center gap-1.5 rounded-[14px] border-[1.5px] border-dashed border-line bg-surface-sunken px-4 py-6 transition hover:border-accent hover:bg-accent-soft"
                >
                    <IconImage className="h-6 w-6 text-ink-faint" />
                    <span className="text-[12.5px] font-bold text-ink-muted">
                        Pilih gambar
                    </span>
                    <span className="text-[11px] text-ink-faint">
                        PNG atau JPG, maksimal 5 MB
                    </span>
                </button>
            )}

            <input
                ref={inputRef}
                type="file"
                accept="image/png,image/jpeg"
                onChange={pick}
                className="hidden"
            />

            <InputError message={error} />
        </div>
    );
}

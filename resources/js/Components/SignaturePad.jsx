import { IconTrash, IconUpload } from '@/Components/Icons';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/**
 * Kotak pembuat tanda tangan digital.
 *
 * Ada dua cara mengisinya dan keduanya bermuara ke SATU kanvas yang sama:
 * digambar langsung (mouse / layar sentuh) atau diambil dari berkas gambar.
 * Karena keluarannya selalu satu bentuk - PNG data URL - backend cuma perlu
 * satu jalur validasi.
 *
 * Dua hal yang dikerjakan di browser sebelum dikirim:
 *
 *  1. Latar putih dihapus (opsional, default nyala). Orang biasanya mengunggah
 *     foto atau scan tanda tangan di atas kertas putih; kalau latarnya tidak
 *     dibuang, yang tertempel di dokumen jadi kotak putih yang menutupi garis
 *     tabel di bawahnya.
 *  2. Pinggiran kosongnya dipangkas. Tanpa ini, orang yang menandatangani di
 *     pojok kanvas menghasilkan gambar yang isinya sebagian besar ruang kosong,
 *     dan waktu dimuat ke kotak tanda tangan di PDF hasilnya jadi kecil sekali.
 */
export default function SignaturePad({ onSaved, submitLabel = 'Simpan Tanda Tangan' }) {
    const canvasRef = useRef(null);
    const ctxRef = useRef(null);
    const drawing = useRef(false);
    const lastPoint = useRef(null);
    const fileRef = useRef(null);

    const [isEmpty, setIsEmpty] = useState(true);
    const [removeWhite, setRemoveWhite] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    // Kanvas disiapkan mengikuti devicePixelRatio supaya garisnya tidak pecah
    // di layar beresolusi tinggi.
    useEffect(() => {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();

        canvas.width = Math.round(rect.width * ratio);
        canvas.height = Math.round(rect.height * ratio);

        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#111827';

        ctxRef.current = ctx;
    }, []);

    const pointFrom = (event) => {
        const rect = canvasRef.current.getBoundingClientRect();

        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    };

    const handlePointerDown = (event) => {
        event.preventDefault();

        const ctx = ctxRef.current;
        const point = pointFrom(event);

        canvasRef.current.setPointerCapture?.(event.pointerId);
        drawing.current = true;
        lastPoint.current = point;

        // Titik kecil supaya sekali ketuk tetap membekas, tidak harus digeser.
        ctx.beginPath();
        ctx.arc(point.x, point.y, ctx.lineWidth / 2, 0, Math.PI * 2);
        ctx.fillStyle = ctx.strokeStyle;
        ctx.fill();

        setIsEmpty(false);
        setError('');
    };

    const handlePointerMove = (event) => {
        if (!drawing.current) {
            return;
        }

        const ctx = ctxRef.current;
        const point = pointFrom(event);

        ctx.beginPath();
        ctx.moveTo(lastPoint.current.x, lastPoint.current.y);
        ctx.lineTo(point.x, point.y);
        ctx.stroke();

        lastPoint.current = point;
    };

    const handlePointerUp = () => {
        drawing.current = false;
    };

    const clear = () => {
        const canvas = canvasRef.current;

        // Bisa saja kanvasnya sudah hilang duluan: begitu tanda tangan
        // tersimpan, gerbang di form pengajuan langsung berganti jadi baris
        // konfirmasi dan komponen ini ikut dilepas - padahal callback
        // onSuccess-nya baru jalan sesudah itu.
        if (!canvas || !ctxRef.current) {
            return;
        }

        const ratio = window.devicePixelRatio || 1;

        ctxRef.current.clearRect(0, 0, canvas.width / ratio, canvas.height / ratio);
        setIsEmpty(true);
        setError('');
    };

    const drawImage = (image) => {
        const canvas = canvasRef.current;
        const ctx = ctxRef.current;
        const ratio = window.devicePixelRatio || 1;
        const boxWidth = canvas.width / ratio;
        const boxHeight = canvas.height / ratio;

        const scale = Math.min(boxWidth / image.width, boxHeight / image.height, 1);
        const width = Math.max(1, Math.round(image.width * scale));
        const height = Math.max(1, Math.round(image.height * scale));

        // Diproses di kanvas sementara dulu supaya penghapusan latar putih
        // tidak ikut memakan coretan yang mungkin sudah ada di kanvas utama.
        const temp = document.createElement('canvas');
        temp.width = width;
        temp.height = height;

        const tempCtx = temp.getContext('2d', { willReadFrequently: true });
        tempCtx.drawImage(image, 0, 0, width, height);

        if (removeWhite) {
            const frame = tempCtx.getImageData(0, 0, width, height);
            const pixels = frame.data;

            for (let i = 0; i < pixels.length; i += 4) {
                const luminance = (pixels[i] + pixels[i + 1] + pixels[i + 2]) / 3;

                if (luminance >= 240) {
                    pixels[i + 3] = 0;
                } else if (luminance > 170) {
                    // Rentang abu-abu muda dibuat separuh transparan biar
                    // pinggiran hurufnya tidak bergerigi.
                    pixels[i + 3] = Math.round(
                        pixels[i + 3] * ((240 - luminance) / 70),
                    );
                }
            }

            tempCtx.putImageData(frame, 0, 0);
        }

        ctx.clearRect(0, 0, boxWidth, boxHeight);
        ctx.drawImage(
            temp,
            (boxWidth - width) / 2,
            (boxHeight - height) / 2,
            width,
            height,
        );

        setIsEmpty(false);
    };

    const handleFile = (event) => {
        const file = event.target.files?.[0];
        event.target.value = '';

        if (!file) {
            return;
        }

        setError('');

        if (!file.type.startsWith('image/')) {
            setError('Berkasnya harus berupa gambar (PNG atau JPG).');

            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            setError('Ukuran berkas maksimal 5 MB.');

            return;
        }

        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            drawImage(image);
            URL.revokeObjectURL(url);
        };

        image.onerror = () => {
            setError('Gambarnya tidak bisa dibaca.');
            URL.revokeObjectURL(url);
        };

        image.src = url;
    };

    /**
     * Ambil isi kanvas, buang pinggiran yang transparan, kembalikan data URL.
     * null berarti kanvasnya memang kosong.
     */
    const exportTrimmed = () => {
        const canvas = canvasRef.current;
        const { width, height } = canvas;
        const { data } = canvas
            .getContext('2d', { willReadFrequently: true })
            .getImageData(0, 0, width, height);

        let top = height;
        let left = width;
        let right = -1;
        let bottom = -1;

        for (let y = 0; y < height; y += 1) {
            for (let x = 0; x < width; x += 1) {
                if (data[(y * width + x) * 4 + 3] > 8) {
                    if (y < top) top = y;
                    if (y > bottom) bottom = y;
                    if (x < left) left = x;
                    if (x > right) right = x;
                }
            }
        }

        if (right < left || bottom < top) {
            return null;
        }

        const padding = 10;
        const cropLeft = Math.max(0, left - padding);
        const cropTop = Math.max(0, top - padding);
        const cropRight = Math.min(width - 1, right + padding);
        const cropBottom = Math.min(height - 1, bottom + padding);

        const output = document.createElement('canvas');
        output.width = cropRight - cropLeft + 1;
        output.height = cropBottom - cropTop + 1;

        output
            .getContext('2d')
            .drawImage(
                canvas,
                cropLeft,
                cropTop,
                output.width,
                output.height,
                0,
                0,
                output.width,
                output.height,
            );

        return output.toDataURL('image/png');
    };

    const save = () => {
        const signature = exportTrimmed();

        if (!signature) {
            setError('Tanda tangannya masih kosong.');

            return;
        }

        setSaving(true);
        setError('');

        router.post(
            route('signature.store'),
            { signature },
            {
                preserveScroll: true,
                onSuccess: () => {
                    clear();
                    onSaved?.();
                },
                onError: (errors) =>
                    setError(errors.signature ?? 'Gagal menyimpan tanda tangan.'),
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <div>
            <div
                className="relative overflow-hidden rounded-2xl border-[1.5px] border-dashed border-line bg-surface"
                style={{
                    // Papan catur tipis supaya bagian yang transparan kelihatan -
                    // ini yang bikin orang sadar latar putihnya sudah terbuang.
                    backgroundImage:
                        'linear-gradient(45deg, var(--color-surface-sunken) 25%, transparent 25%), linear-gradient(-45deg, var(--color-surface-sunken) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, var(--color-surface-sunken) 75%), linear-gradient(-45deg, transparent 75%, var(--color-surface-sunken) 75%)',
                    backgroundSize: '16px 16px',
                    backgroundPosition: '0 0, 0 8px, 8px -8px, -8px 0',
                }}
            >
                <canvas
                    ref={canvasRef}
                    onPointerDown={handlePointerDown}
                    onPointerMove={handlePointerMove}
                    onPointerUp={handlePointerUp}
                    onPointerLeave={handlePointerUp}
                    className="block h-[190px] w-full cursor-crosshair touch-none"
                />

                {isEmpty && (
                    <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1">
                        <span className="text-[13px] font-semibold text-ink-faint">
                            Tanda tangan di sini
                        </span>
                        <span className="text-[11.5px] text-ink-faint">
                            pakai mouse, atau langsung jari kalau layarnya sentuh
                        </span>
                    </div>
                )}
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-2">
                <SecondaryButton
                    onClick={() => fileRef.current?.click()}
                    className="!px-3.5 !py-2 !text-xs"
                >
                    <IconUpload className="h-4 w-4" />
                    Unggah gambar
                </SecondaryButton>

                <SecondaryButton
                    onClick={clear}
                    disabled={isEmpty}
                    className="!px-3.5 !py-2 !text-xs"
                >
                    <IconTrash className="h-4 w-4" />
                    Bersihkan
                </SecondaryButton>

                <label className="ms-1 flex cursor-pointer items-center gap-2 text-[12px] text-ink-muted">
                    <input
                        type="checkbox"
                        checked={removeWhite}
                        onChange={(e) => setRemoveWhite(e.target.checked)}
                        className="h-4 w-4 rounded border-[1.5px] border-line bg-canvas text-accent focus:ring-4 focus:ring-accent-soft focus:ring-offset-0"
                    />
                    Hilangkan latar putih saat mengunggah
                </label>

                <input
                    ref={fileRef}
                    type="file"
                    accept="image/png,image/jpeg"
                    onChange={handleFile}
                    className="hidden"
                />
            </div>

            <InputError message={error} />

            <div className="mt-4 flex justify-end">
                <PrimaryButton onClick={save} disabled={saving || isEmpty}>
                    {saving ? 'Menyimpan...' : submitLabel}
                </PrimaryButton>
            </div>
        </div>
    );
}

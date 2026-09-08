import { IconMoon, IconSun } from '@/Components/Icons';
import { useEffect, useState } from 'react';

/**
 * Tombol ganti tema terang/gelap.
 *
 * Cara kerjanya: tombol ini cuma nulis atribut `data-theme` di elemen <html>.
 * Yang bikin warnanya berubah adalah CSS variable di resources/css/app.css yang
 * memang dipasang berdasarkan atribut itu. Jadi gak ada komponen yang perlu
 * tahu soal tema - mereka cukup pakai class token (bg-surface, text-ink, dst).
 *
 * Pilihan user disimpan di localStorage biar kebawa waktu buka halaman lain
 * atau login lagi besok. Kalau user belum pernah milih, temanya ngikut setting
 * OS lewat media query prefers-color-scheme.
 *
 * Kunci localStorage-nya harus sama persis dengan yang dibaca script kecil di
 * resources/views/app.blade.php - itu yang nyegah "kedip putih" pas halaman
 * pertama kali dimuat.
 */
const STORAGE_KEY = 'visinema-theme';

function systemPrefersDark() {
    return (
        typeof window !== 'undefined' &&
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-color-scheme: dark)').matches
    );
}

function readTheme() {
    if (typeof document === 'undefined') {
        return 'light';
    }

    const attr = document.documentElement.getAttribute('data-theme');

    if (attr === 'light' || attr === 'dark') {
        return attr;
    }

    return systemPrefersDark() ? 'dark' : 'light';
}

export default function ThemeToggle({ className = '' }) {
    const [theme, setTheme] = useState(readTheme);

    // Kalau user belum pernah milih tema sendiri, ikutin perubahan setting OS
    // secara live (misal Windows-nya otomatis gelap pas malam).
    useEffect(() => {
        if (typeof window === 'undefined' || !window.matchMedia) {
            return;
        }

        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const sync = () => setTheme(readTheme());

        media.addEventListener('change', sync);

        return () => media.removeEventListener('change', sync);
    }, []);

    const toggle = () => {
        const next = readTheme() === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', next);
        setTheme(next);

        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch (e) {
            // localStorage diblokir (mode private, dsb) - temanya tetap ganti,
            // cuma gak keinget pas halaman di-refresh.
        }
    };

    const isDark = theme === 'dark';

    return (
        <button
            type="button"
            onClick={toggle}
            aria-pressed={isDark}
            aria-label={isDark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'}
            title={isDark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'}
            className={
                'flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full border-[1.5px] border-line bg-surface text-ink-muted transition hover:-translate-y-px hover:border-accent hover:text-accent-deep focus:outline-none focus:ring-4 focus:ring-accent-soft ' +
                className
            }
        >
            {isDark ? (
                <IconSun className="h-[17px] w-[17px]" />
            ) : (
                <IconMoon className="h-[17px] w-[17px]" />
            )}
        </button>
    );
}

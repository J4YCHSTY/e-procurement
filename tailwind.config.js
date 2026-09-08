import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // ---------------------------------------------------------
                // Token semantik -> nilainya diambil dari CSS variable yang
                // didefinisikan di resources/css/app.css. Karena nilainya
                // variable, satu class yang sama (misal `bg-surface`) otomatis
                // ikut berubah waktu tema terang/gelap ditukar. Jadi gak perlu
                // nulis varian `dark:` di tiap komponen.
                //
                // Catatan: warna berbasis var() gak bisa dipakai bareng
                // modifier opacity (`bg-surface/50`). Kalau butuh transparan,
                // pakai token yang memang sudah transparan (surface-glass,
                // overlay) atau warna literal Tailwind.
                // ---------------------------------------------------------
                canvas: {
                    DEFAULT: 'var(--color-canvas)',
                    deep: 'var(--color-canvas-deep)',
                },
                surface: {
                    DEFAULT: 'var(--color-surface)',
                    sunken: 'var(--color-surface-sunken)',
                    glass: 'var(--color-surface-glass)',
                },
                line: 'var(--color-line)',
                overlay: 'var(--color-overlay)',
                ink: {
                    DEFAULT: 'var(--color-ink)',
                    muted: 'var(--color-ink-muted)',
                    faint: 'var(--color-ink-faint)',
                },
                accent: {
                    DEFAULT: 'var(--color-accent)',
                    deep: 'var(--color-accent-deep)',
                    strong: 'var(--color-accent-strong)',
                    soft: 'var(--color-accent-soft)',
                },
                'on-bright': 'var(--color-on-bright)',
                warning: {
                    DEFAULT: 'var(--color-warning)',
                    soft: 'var(--color-warning-soft)',
                },
                success: {
                    DEFAULT: 'var(--color-success)',
                    soft: 'var(--color-success-soft)',
                },
                danger: {
                    DEFAULT: 'var(--color-danger)',
                    soft: 'var(--color-danger-soft)',
                },
                tag: {
                    blue: 'var(--color-tag-blue)',
                    'blue-soft': 'var(--color-tag-blue-soft)',
                    violet: 'var(--color-tag-violet)',
                    'violet-soft': 'var(--color-tag-violet-soft)',
                },

                // Palet aksen mentah. Token `accent` di atas yang dipakai
                // sehari-hari; skala ini disimpan buat kasus yang butuh shade
                // spesifik. Dipin ke #009BB6 (warna logo Visinema Pictures)
                // di stop 600.
                brand: {
                    50: '#ecfbfe',
                    100: '#d3f7fd',
                    200: '#a3f1ff',
                    300: '#66e8ff',
                    400: '#1addff',
                    500: '#00b6d6',
                    600: '#009bb6',
                    700: '#00798e',
                    800: '#005d6d',
                    900: '#004754',
                    950: '#002e37',
                },
            },
            boxShadow: {
                card: 'var(--shadow-card)',
                pop: 'var(--shadow-pop)',
            },
        },
    },

    plugins: [forms],
};

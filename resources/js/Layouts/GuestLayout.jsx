import ApplicationLogo from '@/Components/ApplicationLogo';
import ThemeToggle from '@/Components/ThemeToggle';
import { Link } from '@inertiajs/react';

/**
 * Layout halaman publik (lupa password, reset password, verifikasi email).
 * Tampilannya sengaja dibikin senada sama halaman login: latar spotlight
 * lembut, satu kartu di tengah, logo di atas.
 */
export default function GuestLayout({ children }) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-canvas px-5 py-12">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_50%_at_50%_0%,var(--color-accent-soft),transparent_70%)]"
            />

            <div className="absolute end-5 top-5 z-10">
                <ThemeToggle />
            </div>

            <Link href="/" className="relative mb-8 flex items-center">
                <ApplicationLogo className="h-[52px] w-auto" />
            </Link>

            <div className="relative w-full overflow-hidden rounded-[26px] border border-line bg-surface px-7 py-9 shadow-card sm:max-w-md sm:px-10">
                {children}
            </div>

            <p className="relative mt-7 text-[11.5px] text-ink-faint">
                &copy; {new Date().getFullYear()}{' '}
                <strong className="font-semibold">Visinema Pictures</strong>
            </p>
        </div>
    );
}

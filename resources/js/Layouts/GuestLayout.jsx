import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-50 px-4 py-10">
            <div
                className="pointer-events-none absolute inset-x-0 -top-40 -z-10 h-96 bg-gradient-to-b from-brand-100 via-brand-50 to-transparent blur-3xl"
                aria-hidden="true"
            />

            <Link href="/" className="mb-8 flex items-center">
                <ApplicationLogo className="h-14 w-auto" />
            </Link>

            <div className="w-full overflow-hidden rounded-2xl border border-slate-200 bg-white px-6 py-8 shadow-card sm:max-w-md sm:px-8">
                {children}
            </div>

            <p className="mt-8 text-xs text-slate-400">
                Sistem Digitalisasi Pengajuan Hardware &amp; Software
            </p>
        </div>
    );
}

import ApplicationLogo from '@/Components/ApplicationLogo';
import {
    IconApp,
    IconCheckBadge,
    IconClipboardList,
    IconDevice,
} from '@/Components/Icons';
import { Head, Link } from '@inertiajs/react';

const FEATURES = [
    {
        icon: IconDevice,
        title: 'Pengajuan Hardware',
        description:
            'Ajukan kebutuhan perangkat (laptop, desktop, peripherals) lengkap dengan rekomendasi standar perusahaan.',
    },
    {
        icon: IconApp,
        title: 'Pengajuan Software',
        description:
            'Ajukan lisensi, subscription, atau addon software dengan detail durasi dan estimasi biaya yang jelas.',
    },
    {
        icon: IconCheckBadge,
        title: 'Approval Berjenjang',
        description:
            'Alur persetujuan dari Head Departemen, Finance, hingga eksekusi administratif oleh tim Procurement.',
    },
    {
        icon: IconClipboardList,
        title: 'Riwayat Transparan',
        description:
            'Pantau status setiap pengajuan secara real-time, dari menunggu persetujuan sampai selesai.',
    },
];

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="Selamat Datang" />
            <div className="relative min-h-screen overflow-hidden bg-slate-50">
                <div
                    className="pointer-events-none absolute inset-x-0 -top-40 -z-10 h-96 bg-gradient-to-b from-brand-100 via-brand-50 to-transparent blur-3xl"
                    aria-hidden="true"
                />

                <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6 lg:px-8">
                    <div className="flex items-center">
                        <ApplicationLogo className="h-11 w-auto" />
                    </div>

                    <nav className="flex items-center gap-3">
                        {auth?.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
                            >
                                Ke Dashboard
                            </Link>
                        ) : (
                            <Link
                                href={route('login')}
                                className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
                            >
                                Log in
                            </Link>
                        )}
                    </nav>
                </header>

                <main className="mx-auto max-w-6xl px-6 pb-24 pt-10 lg:px-8 lg:pt-16">
                    <div className="mx-auto max-w-2xl text-center">
                        <span className="inline-flex items-center rounded-full bg-brand-100 px-3 py-1 text-xs font-semibold text-brand-700">
                            Sistem Digitalisasi Pengajuan
                        </span>
                        <h1 className="mt-5 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                            Ajukan Hardware &amp; Software
                            <span className="text-brand-600"> Lebih Cepat &amp; Transparan</span>
                        </h1>
                        <p className="mt-5 text-base text-slate-600 sm:text-lg">
                            Satu platform untuk mengelola pengajuan perangkat dan
                            software kantor, dari permintaan sampai persetujuan,
                            tanpa perlu bolak-balik kertas atau email.
                        </p>

                        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            {auth?.user ? (
                                <Link
                                    href={route('dashboard')}
                                    className="w-full rounded-lg bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:w-auto"
                                >
                                    Buka Dashboard
                                </Link>
                            ) : (
                                <Link
                                    href={route('login')}
                                    className="w-full rounded-lg bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:w-auto"
                                >
                                    Mulai Sekarang
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="mt-20 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {FEATURES.map((feature) => {
                            const Icon = feature.icon;
                            return (
                                <div
                                    key={feature.title}
                                    className="rounded-xl border border-slate-200 bg-white p-6 shadow-card"
                                >
                                    <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-50">
                                        <Icon className="h-6 w-6 text-brand-600" />
                                    </div>
                                    <h3 className="mt-4 text-sm font-semibold text-slate-900">
                                        {feature.title}
                                    </h3>
                                    <p className="mt-1.5 text-sm leading-relaxed text-slate-500">
                                        {feature.description}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </main>

                <footer className="border-t border-slate-200 bg-white py-6">
                    <p className="text-center text-xs text-slate-400">
                        &copy; {new Date().getFullYear ? new Date().getFullYear() : ''} E-Procurement &mdash; Sistem Digitalisasi Pengajuan Hardware &amp; Software
                    </p>
                </footer>
            </div>
        </>
    );
}

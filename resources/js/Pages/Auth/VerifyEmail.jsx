import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Email Verification" />

            <div className="mb-6">
                <h2 className="text-lg font-semibold text-slate-900">Verifikasi Email</h2>
                <p className="mt-1 text-sm text-slate-500">
                    Terima kasih sudah mendaftar! Sebelum memulai, mohon
                    verifikasi alamat email kamu dengan klik link yang baru
                    saja kami kirimkan. Kalau belum menerima emailnya, kami
                    akan kirimkan lagi dengan senang hati.
                </p>
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700">
                    Link verifikasi baru sudah dikirim ke alamat email yang
                    kamu daftarkan.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        {processing ? 'Mengirim...' : 'Resend Verification Email'}
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-sm font-medium text-slate-500 hover:text-slate-700"
                    >
                        Log Out
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

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
                <h2 className="text-[16.5px] font-extrabold text-ink">Verifikasi Email</h2>
                <p className="mt-1 text-[12.5px] leading-relaxed text-ink-muted">
                    Terima kasih sudah mendaftar! Sebelum memulai, mohon
                    verifikasi alamat email kamu dengan klik link yang baru
                    saja kami kirimkan. Kalau belum menerima emailnya, kami
                    akan kirimkan lagi dengan senang hati.
                </p>
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 rounded-[14px] bg-success-soft px-4 py-3 text-[12.5px] font-semibold text-success">
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
                        className="text-[12.5px] font-semibold text-ink-muted hover:text-ink"
                    >
                        Log Out
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

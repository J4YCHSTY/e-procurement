import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Forgot Password" />

            <div className="mb-6">
                <h2 className="text-lg font-semibold text-slate-900">Lupa Password</h2>
                <p className="mt-1 text-sm text-slate-500">
                    Tidak masalah. Masukkan alamat email kamu dan kami akan
                    mengirimkan link untuk membuat password baru.
                </p>
            </div>

            {status && (
                <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <TextInput
                    id="email"
                    type="email"
                    name="email"
                    value={data.email}
                    className="mt-1 block w-full"
                    isFocused={true}
                    onChange={(e) => setData('email', e.target.value)}
                />

                <InputError message={errors.email} className="mt-2" />

                <div className="flex items-center justify-end pt-2">
                    <PrimaryButton className="w-full justify-center" disabled={processing}>
                        {processing ? 'Mengirim...' : 'Email Password Reset Link'}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}

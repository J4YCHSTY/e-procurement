import ApplicationLogo from '@/Components/ApplicationLogo';
import {
    IconCheckCircle,
    IconEye,
    IconEyeOff,
    IconLockClosed,
    IconMail,
} from '@/Components/Icons';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import ThemeToggle from '@/Components/ThemeToggle';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

function ForgotPasswordModal({ show, onClose, status }) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({
            email: '',
        });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handleClose = () => {
        reset();
        clearErrors();
        onClose();
    };

    return (
        <Modal show={show} onClose={handleClose} maxWidth="md">
            <form onSubmit={submit} className="p-7">
                <h2 className="text-center text-[18px] font-extrabold text-ink">
                    Reset Password
                </h2>
                <p className="mt-2 text-center text-[12.5px] leading-relaxed text-ink-muted">
                    Masukkan alamat email kamu dan kami akan mengirimkan link
                    untuk membuat password baru.
                </p>

                {status && (
                    <div className="mt-4 flex items-center gap-2.5 rounded-[14px] bg-success-soft px-4 py-3 text-[12.5px] font-semibold text-success">
                        <IconCheckCircle className="h-4 w-4 shrink-0" />
                        {status}
                    </div>
                )}

                <div className="mt-5">
                    <label htmlFor="forgot-email" className="form-label">
                        Email Kantor
                    </label>
                    <div className="relative">
                        <IconMail className="pointer-events-none absolute start-4 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-ink-faint" />
                        <TextInput
                            id="forgot-email"
                            type="email"
                            name="email"
                            className="!ps-11"
                            placeholder="nama@visinemapictures.com"
                            value={data.email}
                            isFocused={show}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                    </div>
                    <InputError message={errors.email} />
                </div>

                <div className="mt-6 flex flex-col gap-2.5">
                    <PrimaryButton disabled={processing} className="w-full">
                        {processing ? 'Mengirim...' : 'Kirim Link Reset'}
                    </PrimaryButton>
                    <SecondaryButton onClick={handleClose} className="w-full">
                        Kembali ke Login
                    </SecondaryButton>
                </div>
            </form>
        </Modal>
    );
}

export default function Login({ status, canResetPassword }) {
    const [showForgotPassword, setShowForgotPassword] = useState(false);
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-canvas px-5 py-12">
            <Head title="Log in" />

            {/*
                Latar "spotlight" - dua gradient radial lembut warna aksen.
                Sengaja pointer-events-none biar gak ganggu klik form di atasnya.
            */}
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_50%_at_50%_0%,var(--color-accent-soft),transparent_70%)]"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(45%_40%_at_50%_100%,var(--color-canvas-deep),transparent_70%)]"
            />

            <div className="absolute end-5 top-5 z-10">
                <ThemeToggle />
            </div>

            <main className="relative w-full max-w-[420px]">
                <div className="rounded-[26px] border border-line bg-surface px-7 py-9 shadow-card sm:px-10 sm:py-10">
                    <ApplicationLogo className="mx-auto h-[52px] w-auto" />

                    {status && (
                        <div className="mt-7 flex items-center gap-2.5 rounded-[14px] bg-success-soft px-4 py-3 text-[12.5px] font-semibold text-success">
                            <IconCheckCircle className="h-4 w-4 shrink-0" />
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-8 flex flex-col gap-4">
                        <div>
                            <label htmlFor="email" className="form-label">
                                Email Kantor
                            </label>
                            <div className="relative">
                                <IconMail className="pointer-events-none absolute start-4 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-ink-faint" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    placeholder="nama@visinemapictures.com"
                                    value={data.email}
                                    className="!ps-11 py-3"
                                    autoComplete="username"
                                    isFocused={true}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                />
                            </div>
                            <InputError message={errors.email} />
                        </div>

                        <div>
                            <label htmlFor="password" className="form-label">
                                Password
                            </label>
                            <div className="relative">
                                <IconLockClosed className="pointer-events-none absolute start-4 top-1/2 h-[17px] w-[17px] -translate-y-1/2 text-ink-faint" />
                                <TextInput
                                    id="password"
                                    type={showPassword ? 'text' : 'password'}
                                    name="password"
                                    placeholder="Masukkan password kamu"
                                    value={data.password}
                                    className="!ps-11 !pe-12 py-3"
                                    autoComplete="current-password"
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                />
                                <button
                                    type="button"
                                    onClick={() =>
                                        setShowPassword((prev) => !prev)
                                    }
                                    aria-label={
                                        showPassword
                                            ? 'Sembunyikan password'
                                            : 'Tampilkan password'
                                    }
                                    className="absolute end-3 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-ink-faint transition hover:text-ink"
                                >
                                    {showPassword ? (
                                        <IconEyeOff className="h-[17px] w-[17px]" />
                                    ) : (
                                        <IconEye className="h-[17px] w-[17px]" />
                                    )}
                                </button>
                            </div>
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center justify-between gap-3 pt-1">
                            <label className="flex cursor-pointer items-center gap-2.5">
                                {/*
                                    Switch custom (bukan <input type=checkbox>)
                                    biar bentuknya senada sama desain kartu ini.
                                    Tetap dibungkus <label> + role="switch" supaya
                                    masih kebaca screen reader & bisa diklik dari
                                    teksnya.
                                */}
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={data.remember}
                                    onClick={() =>
                                        setData('remember', !data.remember)
                                    }
                                    className={`relative h-[22px] w-[38px] shrink-0 rounded-full transition focus:outline-none focus:ring-4 focus:ring-accent-soft ${
                                        data.remember ? 'bg-accent' : 'bg-line'
                                    }`}
                                >
                                    <span
                                        className={`absolute top-[3px] h-4 w-4 rounded-full bg-white shadow-sm transition-all ${
                                            data.remember
                                                ? 'left-[19px]'
                                                : 'left-[3px]'
                                        }`}
                                    />
                                </button>
                                <span className="text-[12.5px] font-semibold text-ink-muted">
                                    Ingat saya
                                </span>
                            </label>

                            {canResetPassword && (
                                <button
                                    type="button"
                                    onClick={() => setShowForgotPassword(true)}
                                    className="text-[12.5px] font-semibold text-accent-deep underline decoration-transparent underline-offset-2 transition hover:decoration-current"
                                >
                                    Lupa password?
                                </button>
                            )}
                        </div>

                        <PrimaryButton
                            disabled={processing}
                            className="mt-2 w-full py-3"
                        >
                            {processing ? 'Memproses...' : 'Masuk'}
                        </PrimaryButton>
                    </form>
                </div>

                <p className="mt-6 text-center text-[11.5px] text-ink-faint">
                    &copy; {new Date().getFullYear()}{' '}
                    <strong className="font-semibold">Visinema Pictures</strong>
                </p>
            </main>

            <ForgotPasswordModal
                show={showForgotPassword}
                onClose={() => setShowForgotPassword(false)}
                status={status}
            />
        </div>
    );
}

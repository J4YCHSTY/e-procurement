import { useState } from "react";
import Checkbox from "@/Components/Checkbox";
import InputError from "@/Components/InputError";
import Modal from "@/Components/Modal";
import TextInput from "@/Components/TextInput";
import { Head, useForm } from "@inertiajs/react";

function ForgotPasswordModal({ show, onClose, status }) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        email: "",
    });

    const submit = (e) => {
        e.preventDefault();

        post(route("password.email"), {
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
            <form onSubmit={submit} className="p-8">
                <h2 className="text-center text-2xl font-bold text-slate-900">Reset Password</h2>
                <p className="mt-2 text-center text-sm text-slate-500">
                    Masukkan alamat email kamu dan kami akan mengirimkan link untuk membuat password baru.
                </p>

                {status && (
                    <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700">
                        {status}
                    </div>
                )}

                <div className="mt-6">
                    <label htmlFor="forgot-email" className="mb-1.5 block text-sm font-bold text-slate-700">
                        Email Address
                    </label>
                    <TextInput
                        id="forgot-email"
                        type="email"
                        name="email"
                        className="mt-1 block w-full"
                        placeholder="Placeholder"
                        value={data.email}
                        isFocused={show}
                        onChange={(e) => setData("email", e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-black px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {processing ? "Mengirim..." : "Submit"}
                </button>

                <button
                    type="button"
                    onClick={handleClose}
                    className="mt-4 block w-full text-center text-sm font-medium text-brand-600 hover:text-brand-700"
                >
                    Back to Login
                </button>
            </form>
        </Modal>
    );
}

export default function Login({ status, canResetPassword }) {
    const [showForgotPassword, setShowForgotPassword] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route("login"), {
            onFinish: () => reset("password"),
        });
    };

    return (
        <div className="grid min-h-screen grid-cols-1 grid-rows-[auto_1fr] lg:grid-cols-2 lg:grid-rows-1">
            <Head title="Log in" />

            {/* Panel kiri - branding, full putih biar logo menonjol. Di mobile jadi section atas (compact), di desktop jadi kolom kiri penuh. */}
            <div className="relative flex flex-col items-center justify-center bg-white px-8 py-10 lg:p-12">
                <img
                    src="/images/visinema-pictures.png"
                    alt="Visinema Pictures"
                    className="h-28 w-auto object-contain lg:h-40"
                />
            </div>

            {/* Panel kanan - form login */}
            <div className="flex flex-col items-center justify-center bg-slate-950 px-6 py-12 sm:px-12">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <h2 className="text-2xl font-bold text-white">Sign In</h2>
                        <div className="mt-3 h-0.5 w-full bg-white" />
                    </div>

                    {status && (
                        <div className="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700">
                            {status}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-5">
                        <div>
                            <label htmlFor="email" className="mb-1.5 block text-sm font-bold text-white">
                                Email Address
                            </label>
                            <TextInput
                                id="email"
                                type="email"
                                name="email"
                                placeholder="Email Address"
                                value={data.email}
                                className="mt-1 block w-full"
                                autoComplete="username"
                                isFocused={true}
                                onChange={(e) => setData("email", e.target.value)}
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <label htmlFor="password" className="mb-1.5 block text-sm font-bold text-white">
                                Password
                            </label>
                            <TextInput
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Password"
                                value={data.password}
                                className="mt-1 block w-full"
                                autoComplete="current-password"
                                onChange={(e) => setData("password", e.target.value)}
                            />
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-between">
                            <label className="flex items-center gap-2">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData("remember", e.target.checked)}
                                />
                                <span className="text-sm text-slate-300">Remember Me</span>
                            </label>

                            {canResetPassword && (
                                <button
                                    type="button"
                                    onClick={() => setShowForgotPassword(true)}
                                    className="text-sm font-medium text-brand-400 hover:text-brand-300"
                                >
                                    Forgot password
                                </button>
                            )}
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-black shadow-sm transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {processing ? "Memproses..." : "Sign In"}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <ForgotPasswordModal
                show={showForgotPassword}
                onClose={() => setShowForgotPassword(false)}
                status={status}
            />
        </div>
    );
}

import { IconKey } from '@/Components/Icons';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { useRef } from 'react';

export default function UpdatePasswordForm({ className = '' }) {
    const passwordInput = useRef();
    const currentPasswordInput = useRef();

    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current.focus();
                }
            },
        });
    };

    return (
        <section className={className}>
            <header className="flex items-center gap-2.5">
                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-accent-soft text-accent-deep">
                    <IconKey className="h-4 w-4" />
                </span>
                <div>
                    <h2 className="text-[15.5px] font-extrabold text-ink">
                        Update Password
                    </h2>
                    <p className="mt-0.5 text-[12.5px] text-ink-muted">
                        Pastikan akun kamu menggunakan password yang panjang dan
                        acak agar tetap aman.
                    </p>
                </div>
            </header>

            <form onSubmit={updatePassword} className="mt-5">
                <div className="flex max-w-[420px] flex-col gap-4">
                    <div>
                        <InputLabel
                            htmlFor="current_password"
                            value="Password Saat Ini"
                        />
                        <TextInput
                            id="current_password"
                            ref={currentPasswordInput}
                            value={data.current_password}
                            onChange={(e) =>
                                setData('current_password', e.target.value)
                            }
                            type="password"
                            autoComplete="current-password"
                        />
                        <InputError message={errors.current_password} />
                    </div>

                    <div>
                        <InputLabel htmlFor="password" value="Password Baru" />
                        <TextInput
                            id="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            type="password"
                            autoComplete="new-password"
                        />
                        <p className="mt-1.5 text-[11.5px] text-ink-faint">
                            Minimal 8 karakter, kombinasikan huruf &amp; angka.
                        </p>
                        <InputError message={errors.password} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="password_confirmation"
                            value="Konfirmasi Password Baru"
                        />
                        <TextInput
                            id="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            type="password"
                            autoComplete="new-password"
                        />
                        <InputError message={errors.password_confirmation} />
                    </div>
                </div>

                <div className="mt-5 flex items-center justify-end gap-3 border-t border-line pt-[18px]">
                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-[12.5px] font-semibold text-success">
                            Tersimpan.
                        </p>
                    </Transition>

                    <PrimaryButton disabled={processing}>
                        {processing ? 'Menyimpan...' : 'Simpan Password'}
                    </PrimaryButton>
                </div>
            </form>
        </section>
    );
}

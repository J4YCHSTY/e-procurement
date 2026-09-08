import { IconMail, IconUser } from '@/Components/Icons';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = '',
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
        });

    const submit = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    const needsVerification =
        mustVerifyEmail && user.email_verified_at === null;

    return (
        <section className={className}>
            <header className="flex items-center gap-2.5">
                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-accent-soft text-accent-deep">
                    <IconUser className="h-4 w-4" />
                </span>
                <div>
                    <h2 className="text-[15.5px] font-extrabold text-ink">
                        Informasi Profil
                    </h2>
                    <p className="mt-0.5 text-[12.5px] text-ink-muted">
                        Perbarui nama dan alamat email akun kamu.
                    </p>
                </div>
            </header>

            <form onSubmit={submit} className="mt-5">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="name" value="Nama" />
                        <TextInput
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            isFocused
                            autoComplete="name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <InputLabel htmlFor="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            autoComplete="username"
                        />
                        <InputError message={errors.email} />
                    </div>
                </div>

                {needsVerification ? (
                    <div className="mt-4 flex gap-3 rounded-[14px] bg-warning-soft px-4 py-3.5 text-[12.5px] leading-relaxed text-warning">
                        <IconMail className="mt-px h-[17px] w-[17px] shrink-0" />
                        <div>
                            <strong className="mb-0.5 block text-[13px]">
                                Email kamu belum terverifikasi
                            </strong>
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="font-semibold underline underline-offset-2"
                            >
                                Klik di sini buat kirim ulang email verifikasi.
                            </Link>
                            {status === 'verification-link-sent' && (
                                <div className="mt-1.5 font-semibold text-success">
                                    Link verifikasi baru sudah dikirim ke email
                                    kamu.
                                </div>
                            )}
                        </div>
                    </div>
                ) : (
                    user.email_verified_at && (
                        <div className="mt-4 flex gap-3 rounded-[14px] bg-accent-soft px-4 py-3.5 text-[12.5px] leading-relaxed text-accent-deep">
                            <IconMail className="mt-px h-[17px] w-[17px] shrink-0" />
                            <div>
                                <strong className="mb-0.5 block text-[13px]">
                                    Email sudah terverifikasi
                                </strong>
                                Notifikasi terkait status pengajuan kamu dikirim
                                ke alamat ini.
                            </div>
                        </div>
                    )
                )}

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
                        {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                    </PrimaryButton>
                </div>
            </form>
        </section>
    );
}

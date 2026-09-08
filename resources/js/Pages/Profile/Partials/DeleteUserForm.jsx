import DangerButton from '@/Components/DangerButton';
import { IconAlertTriangle } from '@/Components/Icons';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function DeleteUserForm({ className = '' }) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef();

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <section className={className}>
            <header className="flex items-center gap-2.5">
                <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-danger-soft text-danger">
                    <IconAlertTriangle className="h-4 w-4" />
                </span>
                <div>
                    <h2 className="text-[15.5px] font-extrabold text-ink">
                        Hapus Akun
                    </h2>
                    <p className="mt-0.5 text-[12.5px] text-ink-muted">
                        Tindakan ini bersifat permanen dan tidak bisa
                        dibatalkan.
                    </p>
                </div>
            </header>

            <div className="mt-5 flex gap-3 rounded-[14px] bg-danger-soft px-4 py-3.5 text-[12.5px] leading-relaxed text-danger">
                <IconAlertTriangle className="mt-px h-[17px] w-[17px] shrink-0" />
                <div>
                    <strong className="mb-0.5 block text-[13px]">
                        Perhatikan sebelum melanjutkan
                    </strong>
                    Setelah akun kamu dihapus, seluruh data dan riwayat terkait
                    akan dihapus secara permanen. Unduh atau catat data yang
                    masih kamu perlukan sebelum melanjutkan.
                </div>
            </div>

            <div className="mt-5">
                <DangerButton onClick={confirmUserDeletion}>
                    <IconAlertTriangle className="h-[15px] w-[15px]" />
                    Hapus Akun Saya
                </DangerButton>
            </div>

            <Modal show={confirmingUserDeletion} onClose={closeModal}>
                <form onSubmit={deleteUser} className="p-7">
                    <h2 className="text-[16.5px] font-extrabold text-ink">
                        Yakin ingin menghapus akun kamu?
                    </h2>

                    <p className="mt-2 text-[13px] leading-relaxed text-ink-muted">
                        Setelah akun kamu dihapus, semua resource dan data akan
                        dihapus secara permanen. Masukkan password kamu untuk
                        konfirmasi bahwa kamu ingin menghapus akun ini secara
                        permanen.
                    </p>

                    <div className="mt-5 max-w-[320px]">
                        <InputLabel htmlFor="password" value="Password" />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            isFocused
                            placeholder="Masukkan password kamu"
                        />

                        <InputError message={errors.password} />
                    </div>

                    <div className="mt-6 flex justify-end gap-2.5">
                        <SecondaryButton onClick={closeModal}>
                            Batal
                        </SecondaryButton>

                        <DangerButton disabled={processing}>
                            Hapus Akun
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}

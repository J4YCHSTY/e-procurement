import Checkbox from '@/Components/Checkbox';
import DangerButton from '@/Components/DangerButton';
import {
    IconCheckCircle,
    IconLockClosed,
    IconPencil,
    IconPlus,
    IconPower,
    IconSearch,
    IconShield,
    IconUsers,
    IconXCircle,
} from '@/Components/Icons';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const ROLE_OPTIONS = [
    { value: 'user', label: 'User' },
    { value: 'head', label: 'Kepala Departemen' },
    { value: 'it', label: 'IT' },
    { value: 'finance', label: 'Finance' },
    { value: 'procurement', label: 'Procurement' },
];

// Warna role sengaja beda-beda hue biar kebaca sekilas waktu nge-scan tabel.
// Titik warnanya pakai bg-current supaya ikut warna teksnya, jadi cukup ganti
// satu class buat ganti keduanya.
const ROLE_META = {
    user: { label: 'User', className: 'text-ink-muted' },
    head: { label: 'Kepala Departemen', className: 'text-tag-blue' },
    it: { label: 'IT', className: 'text-tag-violet' },
    finance: { label: 'Finance', className: 'text-warning' },
    procurement: { label: 'Procurement', className: 'text-accent-deep' },
};

function Dot() {
    return (
        <span className="h-[7px] w-[7px] shrink-0 rounded-full bg-current" />
    );
}

function RoleBadge({ role }) {
    const meta = ROLE_META[role] ?? { label: role, className: 'text-ink-muted' };

    return (
        <span
            className={`inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-bold ${meta.className}`}
        >
            <Dot />
            {meta.label}
        </span>
    );
}

function StatusPill({ active }) {
    return active ? (
        <span className="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-success-soft px-2.5 py-1 text-[11.5px] font-bold text-success">
            <IconCheckCircle className="h-3 w-3" />
            Aktif
        </span>
    ) : (
        <span className="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full bg-danger-soft px-2.5 py-1 text-[11.5px] font-bold text-danger">
            <IconXCircle className="h-3 w-3" />
            Nonaktif
        </span>
    );
}

function StatTile({ icon: Icon, label, value, tone }) {
    const toneClass = {
        accent: 'bg-accent-soft text-accent-deep',
        success: 'bg-success-soft text-success',
        danger: 'bg-danger-soft text-danger',
        warning: 'bg-warning-soft text-warning',
    }[tone];

    return (
        <div className="flex items-center gap-3.5 rounded-[18px] border border-line bg-surface px-5 py-[18px] shadow-card">
            <span
                className={`flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-[13px] ${toneClass}`}
            >
                <Icon className="h-5 w-5" />
            </span>
            <div className="min-w-0">
                <div className="text-2xl font-extrabold leading-none tracking-[-0.01em] text-ink">
                    {value}
                </div>
                <div className="mt-1 text-xs font-medium text-ink-muted">
                    {label}
                </div>
            </div>
        </div>
    );
}

function RowAction({ icon: Icon, title, danger = false, ...props }) {
    return (
        <button
            {...props}
            title={title}
            aria-label={title}
            className={`inline-flex h-8 w-8 items-center justify-center rounded-[10px] border-[1.5px] border-line bg-surface text-ink-faint transition disabled:cursor-not-allowed disabled:opacity-35 ${
                danger
                    ? 'hover:border-danger hover:bg-danger-soft hover:text-danger'
                    : 'hover:border-accent hover:bg-accent-soft hover:text-accent-deep'
            } disabled:hover:border-line disabled:hover:bg-surface disabled:hover:text-ink-faint`}
        >
            <Icon className="h-[15px] w-[15px]" />
        </button>
    );
}

function UserFormFields({ form, departements, entities, roleLocked }) {
    const { data, setData, errors } = form;

    // Nilai perusahaan yang nggak ada di daftar resmi (misal sisa data impor
    // lama) tetap dimunculkan sebagai opsi, biar nggak kehapus diam-diam
    // waktu admin sebenernya cuma mau ganti nama orangnya.
    const entityIsLegacy =
        data.entity && !entities.some((opt) => opt.value === data.entity);

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
                <InputLabel htmlFor="name" value="Nama" />
                <TextInput
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    isFocused
                />
                <InputError message={errors.name} />
            </div>

            <div className="sm:col-span-2">
                <InputLabel htmlFor="email" value="Email Kantor" />
                <TextInput
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                />
                <InputError message={errors.email} />
            </div>

            <div>
                <InputLabel htmlFor="entity" value="Perusahaan" />
                <select
                    id="entity"
                    className="form-field"
                    value={data.entity ?? ''}
                    onChange={(e) => setData('entity', e.target.value)}
                >
                    <option value="">-- Belum diatur --</option>
                    {entities.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                    {entityIsLegacy && (
                        <option value={data.entity}>
                            {data.entity} (di luar daftar)
                        </option>
                    )}
                </select>
                <InputError message={errors.entity} />
            </div>

            <div>
                <InputLabel htmlFor="position" value="Jabatan" />
                <TextInput
                    id="position"
                    value={data.position}
                    onChange={(e) => setData('position', e.target.value)}
                />
                <InputError message={errors.position} />
            </div>

            <div>
                <InputLabel htmlFor="departement_id" value="Departemen" />
                <select
                    id="departement_id"
                    className="form-field"
                    value={data.departement_id ?? ''}
                    onChange={(e) => setData('departement_id', e.target.value)}
                >
                    <option value="">-- Belum diatur --</option>
                    {departements.map((dep) => (
                        <option key={dep.id} value={dep.id}>
                            {dep.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.departement_id} />
            </div>

            <div>
                <InputLabel htmlFor="role" value="Role" />
                <select
                    id="role"
                    className="form-field"
                    value={data.role}
                    onChange={(e) => setData('role', e.target.value)}
                    disabled={roleLocked}
                >
                    {ROLE_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
                {roleLocked && (
                    <p className="mt-1.5 text-xs text-ink-faint">
                        Kamu tidak bisa mengubah role akun kamu sendiri. Minta
                        rekan yang lain buat ubah ini.
                    </p>
                )}
                <InputError message={errors.role} />
            </div>

            <div className="sm:col-span-2">
                <label className="flex items-start gap-3 rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5">
                    <Checkbox
                        className="mt-0.5"
                        checked={data.can_manage_users}
                        disabled={roleLocked}
                        onChange={(e) =>
                            setData('can_manage_users', e.target.checked)
                        }
                    />
                    <span>
                        <span className="block text-[13px] font-semibold text-ink">
                            Bisa akses Manajemen User
                        </span>
                        <span className="mt-0.5 block text-[11.5px] leading-relaxed text-ink-muted">
                            Terpisah dari Role di atas &ndash; centang ini kalau
                            user perlu buka menu ini (kelola akun, reset
                            password, dsb), lepas dari apakah dia ikut approve
                            pengajuan atau tidak.
                        </span>
                    </span>
                </label>
                {roleLocked && (
                    <p className="mt-1.5 text-xs text-ink-faint">
                        Kamu tidak bisa mengubah akses Manajemen User akun kamu
                        sendiri. Minta rekan yang lain buat ubah ini.
                    </p>
                )}
                <InputError message={errors.can_manage_users} />
            </div>
        </div>
    );
}

export default function UsersIndex() {
    const { auth, flash, users, departements, entities, filters, stats } =
        usePage().props;
    const currentUserId = auth.user.id;

    const [showCreate, setShowCreate] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [resettingUser, setResettingUser] = useState(null);
    const [togglingUser, setTogglingUser] = useState(null);
    const [busyId, setBusyId] = useState(null);
    const [search, setSearch] = useState(filters?.search ?? '');
    const firstRender = useRef(true);

    // Pencarian dikirim ke server, tapi ditunda 350ms setelah user berhenti
    // ngetik - kalau tiap huruf langsung nembak request, satu kata bisa jadi
    // 6-7 query yang kebuang percuma.
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }

        const timer = setTimeout(() => {
            router.get(
                route('users.index'),
                { search: search || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timer);
    }, [search]);

    const createForm = useForm({
        name: '',
        email: '',
        entity: '',
        position: '',
        departement_id: '',
        role: 'user',
        can_manage_users: false,
    });

    const editForm = useForm({
        name: '',
        email: '',
        entity: '',
        position: '',
        departement_id: '',
        role: 'user',
        can_manage_users: false,
    });

    const openCreate = () => {
        createForm.clearErrors();
        createForm.reset();
        setShowCreate(true);
    };

    const closeCreate = () => {
        setShowCreate(false);
        createForm.reset();
        createForm.clearErrors();
    };

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('users.store'), {
            preserveScroll: true,
            onSuccess: () => closeCreate(),
        });
    };

    const openEdit = (user) => {
        editForm.clearErrors();
        editForm.setData({
            name: user.name ?? '',
            email: user.email ?? '',
            entity: user.entity ?? '',
            position: user.position ?? '',
            departement_id: user.departement_id ?? '',
            role: user.role ?? 'user',
            can_manage_users: user.can_manage_users ?? false,
        });
        setEditingUser(user);
    };

    const closeEdit = () => {
        setEditingUser(null);
        editForm.reset();
        editForm.clearErrors();
    };

    const submitEdit = (e) => {
        e.preventDefault();
        editForm.patch(route('users.update', editingUser.id), {
            preserveScroll: true,
            onSuccess: () => closeEdit(),
        });
    };

    const confirmResetPassword = () => {
        setBusyId(resettingUser.id);
        router.post(
            route('users.reset-password', resettingUser.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setBusyId(null);
                    setResettingUser(null);
                },
            },
        );
    };

    const confirmToggleActive = () => {
        setBusyId(togglingUser.id);
        router.post(
            route('users.toggle-active', togglingUser.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setBusyId(null);
                    setTogglingUser(null);
                },
            },
        );
    };

    const totalAccounts = stats?.total ?? users.total ?? users.data.length;

    return (
        <AuthenticatedLayout
            title="Manajemen User"
            subtitle="Kelola akun & akses karyawan yang terdaftar di sistem ini"
        >
            <Head title="Manajemen User" />

            {flash?.success && (
                <div className="flex items-center gap-2.5 rounded-2xl bg-success-soft px-4 py-3.5 text-[13px] font-semibold text-success">
                    <IconCheckCircle className="h-[17px] w-[17px] shrink-0" />
                    {flash.success}
                </div>
            )}

            <section className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatTile
                    icon={IconUsers}
                    tone="accent"
                    label="Total Akun Terdaftar"
                    value={totalAccounts}
                />
                <StatTile
                    icon={IconCheckCircle}
                    tone="success"
                    label="Akun Aktif"
                    value={stats?.active ?? '-'}
                />
                <StatTile
                    icon={IconPower}
                    tone="danger"
                    label="Akun Nonaktif"
                    value={stats?.inactive ?? '-'}
                />
                <StatTile
                    icon={IconShield}
                    tone="warning"
                    label="Bisa Akses Manajemen User"
                    value={stats?.managers ?? '-'}
                />
            </section>

            <section className="overflow-hidden rounded-[20px] border border-line bg-surface shadow-card">
                <div className="flex flex-wrap items-center justify-between gap-3 p-6 pb-5">
                    <div className="relative w-full max-w-[300px]">
                        <IconSearch className="pointer-events-none absolute left-3 top-1/2 h-[15px] w-[15px] -translate-y-1/2 text-ink-faint" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Cari nama, email, atau jabatan..."
                            className="w-full rounded-[11px] border-[1.5px] border-line bg-surface-sunken py-2.5 pe-3 ps-9 text-[12.5px] text-ink outline-none transition placeholder:text-ink-faint focus:border-accent focus:bg-surface focus:ring-4 focus:ring-accent-soft"
                        />
                    </div>

                    <PrimaryButton onClick={openCreate}>
                        <IconPlus className="h-[15px] w-[15px]" />
                        Tambah User
                    </PrimaryButton>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full border-collapse text-left text-[13px]">
                        <thead>
                            <tr>
                                {[
                                    'Nama',
                                    'Departemen',
                                    'Jabatan',
                                    'Role',
                                    'Manajemen User',
                                    'Status',
                                ].map((label) => (
                                    <th
                                        key={label}
                                        className="whitespace-nowrap border-b border-line px-4 pb-3 text-[10.5px] font-bold uppercase tracking-[0.06em] text-ink-faint"
                                    >
                                        {label}
                                    </th>
                                ))}
                                <th className="whitespace-nowrap border-b border-line px-4 pb-3 text-right text-[10.5px] font-bold uppercase tracking-[0.06em] text-ink-faint">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-14 text-center text-[13px] text-ink-muted"
                                    >
                                        Tidak ada akun yang cocok sama pencarian
                                        kamu.
                                    </td>
                                </tr>
                            )}

                            {users.data.map((user) => {
                                const isSelf = user.id === currentUserId;

                                return (
                                    <tr
                                        key={user.id}
                                        className="border-b border-line transition last:border-b-0 hover:bg-surface-sunken"
                                    >
                                        <td className="px-4 py-3.5">
                                            <div className="flex min-w-0 items-center gap-3">
                                                <span className="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-full bg-accent-soft text-[12.5px] font-bold text-accent-deep">
                                                    {user.name
                                                        ?.charAt(0)
                                                        .toUpperCase()}
                                                </span>
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-1.5 text-[13px] font-bold text-ink">
                                                        {user.name}
                                                        {isSelf && (
                                                            <span className="text-[10.5px] font-semibold text-ink-faint">
                                                                (kamu)
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="text-xs text-ink-muted">
                                                        {user.email}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3.5 text-ink-muted">
                                            {user.departement?.name ?? '-'}
                                        </td>
                                        <td className="px-4 py-3.5 text-ink-muted">
                                            {user.position ?? '-'}
                                        </td>
                                        <td className="px-4 py-3.5">
                                            <RoleBadge role={user.role} />
                                        </td>
                                        <td className="px-4 py-3.5">
                                            {user.can_manage_users ? (
                                                <span className="inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-bold text-success">
                                                    <Dot />
                                                    Bisa akses
                                                </span>
                                            ) : (
                                                <span className="text-xs text-ink-faint">
                                                    &ndash;
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3.5">
                                            <StatusPill active={user.is_active} />
                                        </td>
                                        <td className="px-4 py-3.5">
                                            <div className="flex justify-end gap-1.5">
                                                <RowAction
                                                    icon={IconPencil}
                                                    title="Edit profil"
                                                    onClick={() =>
                                                        openEdit(user)
                                                    }
                                                />
                                                <RowAction
                                                    icon={IconLockClosed}
                                                    title="Reset password ke default"
                                                    onClick={() =>
                                                        setResettingUser(user)
                                                    }
                                                />
                                                <RowAction
                                                    icon={IconPower}
                                                    danger={user.is_active}
                                                    title={
                                                        isSelf
                                                            ? 'Kamu tidak bisa menonaktifkan akun sendiri'
                                                            : user.is_active
                                                              ? 'Nonaktifkan akun'
                                                              : 'Aktifkan akun'
                                                    }
                                                    disabled={isSelf}
                                                    onClick={() =>
                                                        setTogglingUser(user)
                                                    }
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {users.links && users.links.length > 3 && (
                    <div className="flex flex-wrap items-center justify-center gap-1 border-t border-line p-4">
                        {users.links.map((link, index) => (
                            <button
                                key={index}
                                disabled={!link.url}
                                onClick={() =>
                                    link.url &&
                                    router.get(
                                        link.url,
                                        {},
                                        {
                                            preserveScroll: true,
                                            preserveState: true,
                                        },
                                    )
                                }
                                className={`h-8 min-w-8 rounded-[9px] px-2.5 text-xs font-bold transition ${
                                    link.active
                                        ? 'bg-accent text-on-bright'
                                        : link.url
                                          ? 'text-ink-muted hover:bg-surface-sunken hover:text-ink'
                                          : 'cursor-not-allowed text-ink-faint'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </section>

            {/* Modal: Tambah User */}
            <Modal show={showCreate} onClose={closeCreate}>
                <form onSubmit={submitCreate} className="p-7">
                    <h2 className="text-[16.5px] font-extrabold text-ink">
                        Tambah User Baru
                    </h2>
                    <p className="mt-1 text-[12.5px] leading-relaxed text-ink-muted">
                        Akun baru akan dibuat dengan password default. User bisa
                        ganti password sendiri setelah login.
                    </p>

                    <div className="mt-5">
                        <UserFormFields
                            form={createForm}
                            departements={departements}
                            entities={entities}
                            roleLocked={false}
                        />
                    </div>

                    <div className="mt-5 flex justify-end gap-2.5 border-t border-line pt-[18px]">
                        <SecondaryButton onClick={closeCreate}>
                            Batal
                        </SecondaryButton>
                        <PrimaryButton disabled={createForm.processing}>
                            Simpan
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal: Edit User */}
            <Modal show={editingUser !== null} onClose={closeEdit}>
                <form onSubmit={submitEdit} className="p-7">
                    <h2 className="text-[16.5px] font-extrabold text-ink">
                        Edit Profil User
                    </h2>
                    <p className="mt-1 text-[12.5px] text-ink-muted">
                        {editingUser?.name}
                    </p>

                    <div className="mt-5">
                        {editingUser && (
                            <UserFormFields
                                form={editForm}
                                departements={departements}
                                entities={entities}
                                roleLocked={editingUser.id === currentUserId}
                            />
                        )}
                    </div>

                    <div className="mt-5 flex justify-end gap-2.5 border-t border-line pt-[18px]">
                        <SecondaryButton onClick={closeEdit}>
                            Batal
                        </SecondaryButton>
                        <PrimaryButton disabled={editForm.processing}>
                            Simpan Perubahan
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal: Konfirmasi Reset Password */}
            <Modal
                show={resettingUser !== null}
                onClose={() => setResettingUser(null)}
                maxWidth="md"
            >
                <div className="p-7">
                    <h2 className="text-[16.5px] font-extrabold text-ink">
                        Reset Password?
                    </h2>
                    <p className="mt-2 text-[13px] leading-relaxed text-ink-muted">
                        Password{' '}
                        <strong className="text-ink">
                            {resettingUser?.name}
                        </strong>{' '}
                        akan dikembalikan ke password default. Beri tahu user
                        yang bersangkutan supaya bisa login kembali lalu ganti
                        passwordnya sendiri.
                    </p>

                    <div className="mt-6 flex justify-end gap-2.5">
                        <SecondaryButton onClick={() => setResettingUser(null)}>
                            Batal
                        </SecondaryButton>
                        <PrimaryButton
                            onClick={confirmResetPassword}
                            disabled={busyId === resettingUser?.id}
                        >
                            Ya, Reset Password
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            {/* Modal: Konfirmasi Nonaktifkan/Aktifkan */}
            <Modal
                show={togglingUser !== null}
                onClose={() => setTogglingUser(null)}
                maxWidth="md"
            >
                <div className="p-7">
                    <h2 className="text-[16.5px] font-extrabold text-ink">
                        {togglingUser?.is_active
                            ? 'Nonaktifkan Akun?'
                            : 'Aktifkan Kembali Akun?'}
                    </h2>
                    <p className="mt-2 text-[13px] leading-relaxed text-ink-muted">
                        {togglingUser?.is_active ? (
                            <>
                                <strong className="text-ink">
                                    {togglingUser?.name}
                                </strong>{' '}
                                tidak akan bisa login lagi sampai akunnya
                                diaktifkan kembali. Riwayat pengajuan &amp;
                                approval yang sudah ada tetap tersimpan.
                            </>
                        ) : (
                            <>
                                <strong className="text-ink">
                                    {togglingUser?.name}
                                </strong>{' '}
                                akan bisa login kembali seperti biasa.
                            </>
                        )}
                    </p>

                    <div className="mt-6 flex justify-end gap-2.5">
                        <SecondaryButton onClick={() => setTogglingUser(null)}>
                            Batal
                        </SecondaryButton>
                        {togglingUser?.is_active ? (
                            <DangerButton
                                onClick={confirmToggleActive}
                                disabled={busyId === togglingUser?.id}
                            >
                                Ya, Nonaktifkan
                            </DangerButton>
                        ) : (
                            <PrimaryButton
                                onClick={confirmToggleActive}
                                disabled={busyId === togglingUser?.id}
                            >
                                Ya, Aktifkan
                            </PrimaryButton>
                        )}
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

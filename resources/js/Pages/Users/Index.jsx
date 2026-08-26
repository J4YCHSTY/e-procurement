import { useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Modal from "@/Components/Modal";
import InputLabel from "@/Components/InputLabel";
import InputError from "@/Components/InputError";
import TextInput from "@/Components/TextInput";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import DangerButton from "@/Components/DangerButton";
import {
    IconLockClosed,
    IconPencil,
    IconPlus,
    IconPower,
    IconUsers,
} from "@/Components/Icons";
import { Head, useForm, usePage, router } from "@inertiajs/react";

const ROLE_OPTIONS = [
    { value: "user", label: "User" },
    { value: "head", label: "Kepala Departemen" },
    { value: "it", label: "IT" },
    { value: "finance", label: "Finance" },
    { value: "procurement", label: "Procurement" },
];

// Label & warna role, gaya sama kayak StatusBadge di Dashboard (dot + text,
// tanpa background) biar konsisten sama desain yang udah dipakai di sana.
const ROLE_META = {
    user: { label: "User", className: "text-slate-500" },
    head: { label: "Kepala Departemen", className: "text-blue-600" },
    it: { label: "IT", className: "text-violet-600" },
    finance: { label: "Finance", className: "text-amber-600" },
    procurement: { label: "Procurement", className: "text-brand-600" },
};

function Dot({ className }) {
    return <span className={`h-1.5 w-1.5 shrink-0 rounded-full bg-current ${className}`} />;
}

function RoleBadge({ role }) {
    const meta = ROLE_META[role] ?? { label: role, className: "text-slate-500" };
    return (
        <span className={`inline-flex items-center gap-1.5 text-xs font-semibold ${meta.className}`}>
            <Dot />
            {meta.label}
        </span>
    );
}

function ActiveBadge({ active }) {
    return active ? (
        <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600">
            <Dot />
            Aktif
        </span>
    ) : (
        <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-600">
            <Dot />
            Nonaktif
        </span>
    );
}

function UserFormFields({ form, departements, roleLocked }) {
    const { data, setData, errors } = form;

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
                <InputLabel htmlFor="name" value="Nama" />
                <TextInput
                    id="name"
                    className="mt-1 block w-full"
                    value={data.name}
                    onChange={(e) => setData("name", e.target.value)}
                    isFocused
                />
                <InputError message={errors.name} />
            </div>

            <div className="sm:col-span-2">
                <InputLabel htmlFor="email" value="Email Kantor" />
                <TextInput
                    id="email"
                    type="email"
                    className="mt-1 block w-full"
                    value={data.email}
                    onChange={(e) => setData("email", e.target.value)}
                />
                <InputError message={errors.email} />
            </div>

            <div>
                <InputLabel htmlFor="entity" value="Perusahaan" />
                <TextInput
                    id="entity"
                    className="mt-1 block w-full"
                    value={data.entity}
                    onChange={(e) => setData("entity", e.target.value)}
                />
                <InputError message={errors.entity} />
            </div>

            <div>
                <InputLabel htmlFor="position" value="Jabatan" />
                <TextInput
                    id="position"
                    className="mt-1 block w-full"
                    value={data.position}
                    onChange={(e) => setData("position", e.target.value)}
                />
                <InputError message={errors.position} />
            </div>

            <div>
                <InputLabel htmlFor="departement_id" value="Departemen" />
                <select
                    id="departement_id"
                    className="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    value={data.departement_id ?? ""}
                    onChange={(e) => setData("departement_id", e.target.value)}
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
                    className="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                    value={data.role}
                    onChange={(e) => setData("role", e.target.value)}
                    disabled={roleLocked}
                >
                    {ROLE_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
                {roleLocked && (
                    <p className="mt-1.5 text-xs text-slate-400">
                        Kamu tidak bisa mengubah role akun kamu sendiri. Minta IT lain buat ubah ini.
                    </p>
                )}
                <InputError message={errors.role} />
            </div>
        </div>
    );
}

export default function UsersIndex() {
    const { auth, flash, users, departements } = usePage().props;
    const currentUserId = auth.user.id;

    const [showCreate, setShowCreate] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [resettingUser, setResettingUser] = useState(null);
    const [togglingUser, setTogglingUser] = useState(null);
    const [busyId, setBusyId] = useState(null);

    const createForm = useForm({
        name: "",
        email: "",
        entity: "",
        position: "",
        departement_id: "",
        role: "user",
    });

    const editForm = useForm({
        name: "",
        email: "",
        entity: "",
        position: "",
        departement_id: "",
        role: "user",
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
        createForm.post(route("users.store"), {
            preserveScroll: true,
            onSuccess: () => closeCreate(),
        });
    };

    const openEdit = (user) => {
        editForm.clearErrors();
        editForm.setData({
            name: user.name ?? "",
            email: user.email ?? "",
            entity: user.entity ?? "",
            position: user.position ?? "",
            departement_id: user.departement_id ?? "",
            role: user.role ?? "user",
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
        editForm.patch(route("users.update", editingUser.id), {
            preserveScroll: true,
            onSuccess: () => closeEdit(),
        });
    };

    const confirmResetPassword = () => {
        setBusyId(resettingUser.id);
        router.post(
            route("users.reset-password", resettingUser.id),
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
            route("users.toggle-active", togglingUser.id),
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

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-base font-semibold text-slate-900">Manajemen User</h2>
                    <p className="text-xs text-slate-400">Kelola akun & profil karyawan yang punya akses ke sistem ini</p>
                </div>
            }
        >
            <Head title="Manajemen User" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {flash?.success && (
                    <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                        {flash.success}
                    </div>
                )}

                <div className="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-card">
                    <div className="flex items-center gap-3">
                        <IconUsers className="h-6 w-6 shrink-0 text-slate-900" />
                        <div>
                            <p className="text-2xl font-bold leading-tight text-slate-900">{users.total ?? users.data.length}</p>
                            <p className="text-sm text-slate-500">Total akun terdaftar</p>
                        </div>
                    </div>

                    <PrimaryButton onClick={openCreate}>
                        <IconPlus className="h-4 w-4" />
                        Tambah User
                    </PrimaryButton>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white shadow-card">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Nama</th>
                                    <th className="px-6 py-3 font-medium">Email</th>
                                    <th className="px-6 py-3 font-medium">Departemen</th>
                                    <th className="px-6 py-3 font-medium">Jabatan</th>
                                    <th className="px-6 py-3 font-medium">Role</th>
                                    <th className="px-6 py-3 font-medium">Status</th>
                                    <th className="px-6 py-3 text-right font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {users.data.map((user) => {
                                    const isSelf = user.id === currentUserId;

                                    return (
                                        <tr key={user.id} className="hover:bg-slate-50">
                                            <td className="px-6 py-4 font-medium text-slate-800">
                                                {user.name}
                                                {isSelf && <span className="ml-1.5 text-xs font-normal text-slate-400">(kamu)</span>}
                                            </td>
                                            <td className="px-6 py-4 text-slate-600">{user.email}</td>
                                            <td className="px-6 py-4 text-slate-600">{user.departement?.name ?? "-"}</td>
                                            <td className="px-6 py-4 text-slate-600">{user.position ?? "-"}</td>
                                            <td className="px-6 py-4"><RoleBadge role={user.role} /></td>
                                            <td className="px-6 py-4"><ActiveBadge active={user.is_active} /></td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        title="Edit profil"
                                                        onClick={() => openEdit(user)}
                                                        className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                    >
                                                        <IconPencil className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        title="Reset password ke default"
                                                        onClick={() => setResettingUser(user)}
                                                        className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                    >
                                                        <IconLockClosed className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        title={isSelf ? "Kamu tidak bisa menonaktifkan akun sendiri" : user.is_active ? "Nonaktifkan akun" : "Aktifkan akun"}
                                                        onClick={() => setTogglingUser(user)}
                                                        disabled={isSelf}
                                                        className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent"
                                                    >
                                                        <IconPower className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {users.links && users.links.length > 3 && (
                        <div className="flex flex-wrap items-center justify-center gap-1 border-t border-slate-100 p-4">
                            {users.links.map((link, index) => (
                                <button
                                    key={index}
                                    disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true, preserveState: true })}
                                    className={`min-w-9 rounded-lg px-3 py-1.5 text-xs font-medium transition ${
                                        link.active
                                            ? "bg-brand-600 text-white"
                                            : link.url
                                              ? "text-slate-600 hover:bg-slate-100"
                                              : "cursor-not-allowed text-slate-300"
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Modal: Tambah User */}
            <Modal show={showCreate} onClose={closeCreate}>
                <form onSubmit={submitCreate} className="p-6">
                    <h2 className="text-lg font-semibold text-slate-900">Tambah User Baru</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Akun baru akan dibuat dengan password default. User bisa ganti password sendiri setelah login.
                    </p>

                    <div className="mt-6">
                        <UserFormFields form={createForm} departements={departements} roleLocked={false} />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeCreate}>Batal</SecondaryButton>
                        <PrimaryButton disabled={createForm.processing}>Simpan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal: Edit User */}
            <Modal show={editingUser !== null} onClose={closeEdit}>
                <form onSubmit={submitEdit} className="p-6">
                    <h2 className="text-lg font-semibold text-slate-900">Edit Profil User</h2>
                    <p className="mt-1 text-sm text-slate-500">{editingUser?.name}</p>

                    <div className="mt-6">
                        {editingUser && (
                            <UserFormFields
                                form={editForm}
                                departements={departements}
                                roleLocked={editingUser.id === currentUserId}
                            />
                        )}
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeEdit}>Batal</SecondaryButton>
                        <PrimaryButton disabled={editForm.processing}>Simpan Perubahan</PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal: Konfirmasi Reset Password */}
            <Modal show={resettingUser !== null} onClose={() => setResettingUser(null)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-slate-900">Reset Password?</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Password <strong>{resettingUser?.name}</strong> akan dikembalikan ke password default. Beri tahu user yang
                        bersangkutan supaya bisa login kembali lalu ganti passwordnya sendiri.
                    </p>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setResettingUser(null)}>Batal</SecondaryButton>
                        <PrimaryButton onClick={confirmResetPassword} disabled={busyId === resettingUser?.id}>
                            Ya, Reset Password
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            {/* Modal: Konfirmasi Nonaktifkan/Aktifkan */}
            <Modal show={togglingUser !== null} onClose={() => setTogglingUser(null)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-slate-900">
                        {togglingUser?.is_active ? "Nonaktifkan Akun?" : "Aktifkan Kembali Akun?"}
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        {togglingUser?.is_active ? (
                            <>
                                <strong>{togglingUser?.name}</strong> tidak akan bisa login lagi sampai akunnya diaktifkan
                                kembali. Riwayat pengajuan & approval yang sudah ada tetap tersimpan.
                            </>
                        ) : (
                            <>
                                <strong>{togglingUser?.name}</strong> akan bisa login kembali seperti biasa.
                            </>
                        )}
                    </p>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setTogglingUser(null)}>Batal</SecondaryButton>
                        {togglingUser?.is_active ? (
                            <DangerButton onClick={confirmToggleActive} disabled={busyId === togglingUser?.id}>
                                Ya, Nonaktifkan
                            </DangerButton>
                        ) : (
                            <PrimaryButton onClick={confirmToggleActive} disabled={busyId === togglingUser?.id}>
                                Ya, Aktifkan
                            </PrimaryButton>
                        )}
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

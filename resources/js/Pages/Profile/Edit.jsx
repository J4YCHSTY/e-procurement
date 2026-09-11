import {
  IconAlertTriangle,
  IconKey,
  IconPencil,
  IconUser,
} from "@/Components/Icons";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import { useState } from "react";
import DeleteUserForm from "./Partials/DeleteUserForm";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm";
import UpdateSignatureForm from "./Partials/UpdateSignatureForm";

// Label & warna role disamain sama yang dipakai di halaman Manajemen User
// biar orang yang sama kelihatan konsisten di dua halaman.
const ROLE_META = {
  user: { label: "User", className: "text-ink-muted" },
  head: { label: "Kepala Departemen", className: "text-tag-blue" },
  it_head: { label: "Head of IT", className: "text-accent-deep" },
  it: { label: "IT Admin", className: "text-tag-violet" },
};

const TABS = [
  { id: "info", label: "Informasi Profil", icon: IconUser },
  { id: "signature", label: "Tanda Tangan", icon: IconPencil },
  { id: "security", label: "Keamanan", icon: IconKey },
  { id: "danger", label: "Zona Bahaya", icon: IconAlertTriangle },
];

export default function Edit({ mustVerifyEmail, status }) {
  const user = usePage().props.auth.user;
  const [activeTab, setActiveTab] = useState("info");

  const roleMeta = ROLE_META[user.role] ?? {
    label: user.role,
    className: "text-ink-muted",
  };

  return (
    <AuthenticatedLayout
      title="Profil Saya"
      subtitle="Kelola informasi akun & keamanan kamu sendiri"
    >
      <Head title="Profil Saya" />

      <section className="flex items-center gap-[18px] rounded-[20px] border border-line bg-surface px-[26px] py-6 shadow-card">
        <span className="flex h-[62px] w-[62px] shrink-0 items-center justify-center rounded-full bg-accent-soft text-[22px] font-extrabold text-accent-deep">
          {user.name?.charAt(0).toUpperCase()}
        </span>
        <div className="min-w-0">
          <h2 className="text-lg font-extrabold text-ink">{user.name}</h2>
          <div className="mt-1 flex flex-wrap items-center gap-2">
            <span
              className={`inline-flex items-center gap-1.5 text-xs font-bold ${roleMeta.className}`}
            >
              <span className="h-[7px] w-[7px] shrink-0 rounded-full bg-current" />
              {roleMeta.label}
            </span>
            <span className="text-ink-faint">&middot;</span>
            <span className="text-[12.5px] text-ink-muted">
              {user.position ?? "-"}
              {user.entity ? ` · ${user.entity}` : ""}
            </span>
          </div>
        </div>
      </section>

      <section className="overflow-hidden rounded-[20px] border border-line bg-surface shadow-card">
        <div className="flex gap-1 overflow-x-auto border-b border-line bg-surface-sunken p-2.5">
          {TABS.map((tab) => {
            const Icon = tab.icon;
            const active = activeTab === tab.id;

            return (
              <button
                key={tab.id}
                type="button"
                onClick={() => setActiveTab(tab.id)}
                className={`flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 py-2.5 text-[13px] font-bold transition ${
                  active
                    ? "bg-accent text-on-bright shadow-pop"
                    : "text-ink-muted hover:bg-surface hover:text-ink"
                }`}
              >
                <Icon className="h-[15px] w-[15px]" />
                {tab.label}
              </button>
            );
          })}
        </div>

        <div className="p-6 sm:p-7">
          {activeTab === "info" && (
            <UpdateProfileInformationForm
              mustVerifyEmail={mustVerifyEmail}
              status={status}
            />
          )}

          {activeTab === "signature" && <UpdateSignatureForm />}

          {activeTab === "security" && <UpdatePasswordForm />}

          {activeTab === "danger" && <DeleteUserForm />}
        </div>
      </section>
    </AuthenticatedLayout>
  );
}

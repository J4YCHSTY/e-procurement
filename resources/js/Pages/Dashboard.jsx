import { useState } from "react";
import HardwareForm from "./Hardware";
import SoftwareForm from "./Software";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import {
    IconApp,
    IconCheckBadge,
    IconClipboardList,
    IconClock,
    IconDevice,
    IconInbox,
    IconPlus,
    IconX,
} from "@/Components/Icons";
import { Head, usePage, router } from "@inertiajs/react";

// Label & warna badge status, disamain sama App\Enums\RequestStatus di backend.
const STATUS_META = {
    WAITING_FOR_HEAD_APPROVAL: { label: "Menunggu Approval Kepala Departemen", className: "text-amber-600" },
    WAITING_FOR_IT_APPROVAL: { label: "Menunggu Approval Tim IT", className: "text-amber-600" },
    WAITING_FOR_FINANCE_APPROVAL: { label: "Menunggu Approval Finance", className: "text-amber-600" },
    APPROVED: { label: "Disetujui", className: "text-emerald-600" },
    COMPLETED: { label: "Selesai", className: "text-brand-600" },
    REJECTED: { label: "Ditolak", className: "text-rose-600" },
};

const TABS = [
    { id: "create", label: "Buat Pengajuan", icon: IconPlus },
    { id: "history", label: "Riwayat Pengajuan", icon: IconClipboardList },
    { id: "approval", label: "Approval", icon: IconCheckBadge, approverOnly: true },
    { id: "approval_history", label: "Riwayat Approval", icon: IconClock, approverOnly: true },
];

// Label & warna buat keputusan approver sendiri terhadap satu pengajuan,
// disamain sama nilai 'your_decision' yang dihitung backend di
// DashboardController::approvalHistoryFor().
const DECISION_META = {
    approved: { label: "Anda Setujui", className: "text-emerald-600" },
    rejected: { label: "Anda Tolak", className: "text-rose-600" },
    completed: { label: "Anda Tandai Selesai", className: "text-brand-600" },
};

function StatusBadge({ status }) {
    const meta = STATUS_META[status] ?? { label: status, className: "text-slate-500" };
    return (
        <span className={`inline-flex items-center gap-1.5 text-xs font-semibold ${meta.className}`}>
            <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-current" />
            {meta.label}
        </span>
    );
}

function DecisionBadge({ decision }) {
    const meta = DECISION_META[decision] ?? { label: decision, className: "text-slate-500" };
    return (
        <span className={`inline-flex items-center gap-1.5 text-xs font-semibold ${meta.className}`}>
            <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-current" />
            {meta.label}
        </span>
    );
}

function StatCard({ icon: Icon, label, value }) {
    return (
        <div className="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-card">
            <Icon className="h-6 w-6 shrink-0 text-slate-900" />
            <div>
                <p className="text-2xl font-bold leading-tight text-slate-900">{value}</p>
                <p className="text-sm text-slate-500">{label}</p>
            </div>
        </div>
    );
}

export default function Dashboard() {
    const { auth, flash, hardwareHistory, softwareHistory, pendingApprovals, approvalHistory, canApprove } = usePage().props;
    const user = auth.user;
    const [onRequest, setRequest] = useState("");
    const [activeTab, setActiveTab] = useState("create");
    const [processingId, setProcessingId] = useState(null);

    const handleApproval = (item, action) => {
        const routeName = `request.${item.type.toLowerCase()}.${action}`;
        setProcessingId(item.id);
        router.post(route(routeName, item.id), {}, {
            preserveScroll: true,
            onFinish: () => setProcessingId(null),
        });
    };

    const allHistory = [...(hardwareHistory ?? []), ...(softwareHistory ?? [])];
    const waitingCount = allHistory.filter((r) => r.status?.startsWith("WAITING_")).length;
    const completedCount = allHistory.filter((r) => r.status === "COMPLETED" || r.status === "APPROVED").length;
    const rejectedCount = allHistory.filter((r) => r.status === "REJECTED").length;
    const pendingCount = pendingApprovals?.length ?? 0;

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-base font-semibold text-slate-900">Dashboard Pengajuan</h2>
                    <p className="text-xs text-slate-400">Selamat datang kembali, {user.name?.split(" ")[0]}</p>
                </div>
            }
        >
            <Head title="Pengajuan" />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {flash?.success && (
                    <div className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                        {flash.success}
                    </div>
                )}

                {canApprove && pendingCount > 0 && (
                    <button
                        onClick={() => setActiveTab("approval")}
                        className="flex w-full items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-left transition hover:bg-amber-100"
                    >
                        <span className="flex items-center gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                                <IconInbox className="h-5 w-5" />
                            </span>
                            <span className="text-sm font-medium text-amber-900">
                                Ada <strong>{pendingCount}</strong> pengajuan yang menunggu approval kamu.
                            </span>
                        </span>
                        <span className="text-sm font-semibold text-amber-700">Lihat &rarr;</span>
                    </button>
                )}

                {/* Ringkasan */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatCard
                        icon={IconClipboardList}
                        label="Total Pengajuan"
                        value={allHistory.length}
                    />
                    <StatCard
                        icon={IconClock}
                        label="Sedang Diproses"
                        value={waitingCount}
                    />
                    <StatCard
                        icon={IconCheckBadge}
                        label="Disetujui / Selesai"
                        value={completedCount}
                    />
                    <StatCard
                        icon={IconX}
                        label="Ditolak"
                        value={rejectedCount}
                    />
                </div>

                {/* User info ringkas */}
                <div className="grid grid-cols-2 gap-4 rounded-xl border border-slate-200 bg-white p-5 text-sm shadow-card sm:grid-cols-4">
                    <div>
                        <span className="block text-xs font-medium uppercase tracking-wide text-slate-400">Nama</span>
                        <span className="font-medium text-slate-800">{user.name}</span>
                    </div>
                    <div>
                        <span className="block text-xs font-medium uppercase tracking-wide text-slate-400">Email</span>
                        <span className="font-medium text-slate-800">{user.email}</span>
                    </div>
                    <div>
                        <span className="block text-xs font-medium uppercase tracking-wide text-slate-400">Perusahaan</span>
                        <span className="font-medium text-slate-800">{user.entity ?? "-"}</span>
                    </div>
                    <div>
                        <span className="block text-xs font-medium uppercase tracking-wide text-slate-400">Jabatan</span>
                        <span className="font-medium text-slate-800">{user.position ?? "-"}</span>
                    </div>
                </div>

                {/* Tab navigasi */}
                <div className="flex flex-wrap gap-2 border-b border-slate-200 pb-px">
                    {TABS.filter((tab) => !tab.approverOnly || canApprove).map((tab) => {
                        const Icon = tab.icon;
                        const active = activeTab === tab.id;
                        return (
                            <button
                                key={tab.id}
                                onClick={() => setActiveTab(tab.id)}
                                className={`relative flex items-center gap-2 rounded-t-lg px-4 py-2.5 text-sm font-semibold transition ${
                                    active
                                        ? "border-b-2 border-brand-600 text-brand-700"
                                        : "border-b-2 border-transparent text-slate-500 hover:text-slate-800"
                                }`}
                            >
                                <Icon className="h-4 w-4" />
                                {tab.label}
                                {tab.id === "approval" && pendingCount > 0 && (
                                    <span className="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-xs font-bold text-white">
                                        {pendingCount}
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* Buat Pengajuan */}
                {activeTab === "create" && (
                    <div className="space-y-6">
                        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card">
                            <label className="mb-2 block text-sm font-semibold text-slate-700">
                                Pilih Kategori Pengajuan
                            </label>
                            <select
                                className="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 md:w-1/3"
                                value={onRequest}
                                onChange={(e) => setRequest(e.target.value)}
                            >
                                <option value="">-- Silahkan Pilih Kategori Pengajuan --</option>
                                <option value="HARDWARE">Hardware</option>
                                <option value="SOFTWARE">Software</option>
                            </select>
                        </div>

                        {onRequest === "HARDWARE" && <HardwareForm user={user} />}
                        {onRequest === "SOFTWARE" && <SoftwareForm user={user} />}
                    </div>
                )}

                {/* Riwayat Pengajuan */}
                {activeTab === "history" && (
                    <div className="rounded-xl border border-slate-200 bg-white shadow-card">
                        <div className="border-b border-slate-100 p-6 pb-4">
                            <h3 className="text-base font-semibold text-slate-900">Riwayat Pengajuan</h3>
                        </div>

                        {allHistory.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 px-6 py-14 text-center">
                                <IconInbox className="h-8 w-8 text-slate-300" />
                                <p className="text-sm text-slate-500">
                                    Kamu belum pernah mengajukan form request apapun.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-400">
                                        <tr>
                                            <th className="px-6 py-3 font-medium">Tanggal</th>
                                            <th className="px-6 py-3 font-medium">Kategori</th>
                                            <th className="px-6 py-3 font-medium">Spesifikasi / Nama Item</th>
                                            <th className="px-6 py-3 font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {hardwareHistory?.map((req) => (
                                            <tr key={`hw-${req.id}`} className="hover:bg-slate-50">
                                                <td className="whitespace-nowrap px-6 py-4 text-slate-600">{req.request_date}</td>
                                                <td className="px-6 py-4">
                                                    <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600">
                                                        <IconDevice className="h-3.5 w-3.5" />
                                                        Hardware
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 font-medium text-slate-800">{req.hardware_type}</td>
                                                <td className="px-6 py-4"><StatusBadge status={req.status} /></td>
                                            </tr>
                                        ))}

                                        {softwareHistory?.map((req) => (
                                            <tr key={`sw-${req.id}`} className="hover:bg-slate-50">
                                                <td className="whitespace-nowrap px-6 py-4 text-slate-600">{req.request_date}</td>
                                                <td className="px-6 py-4">
                                                    <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-violet-600">
                                                        <IconApp className="h-3.5 w-3.5" />
                                                        Software
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 font-medium text-slate-800">{req.software_name}</td>
                                                <td className="px-6 py-4"><StatusBadge status={req.status} /></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* Approval */}
                {activeTab === "approval" && canApprove && (
                    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card">
                        <h3 className="text-base font-semibold text-slate-900">Daftar Pengajuan Perlu Ditinjau</h3>
                        <p className="mb-6 mt-1 text-sm text-slate-500">
                            Setujui atau tolak pengajuan yang masuk ke tahap kamu.
                        </p>

                        {pendingCount === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-14 text-center">
                                <IconCheckBadge className="h-8 w-8 text-slate-300" />
                                <p className="text-sm text-slate-500">Tidak ada pengajuan yang aktif saat ini.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4">
                                {pendingApprovals.map((item) => (
                                    <div
                                        key={`${item.type}-${item.id}`}
                                        className="flex flex-col gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-slate-300 md:flex-row md:items-center md:justify-between"
                                    >
                                        <div className="flex-1">
                                            <div className="mb-1 flex flex-wrap items-center gap-2">
                                                <span
                                                    className={`inline-flex items-center gap-1.5 text-xs font-semibold ${
                                                        item.type === "Hardware" ? "text-blue-600" : "text-violet-600"
                                                    }`}
                                                >
                                                    {item.type === "Hardware" ? (
                                                        <IconDevice className="h-3.5 w-3.5" />
                                                    ) : (
                                                        <IconApp className="h-3.5 w-3.5" />
                                                    )}
                                                    {item.type}
                                                </span>
                                                <span className="text-xs font-medium text-slate-400">{item.request_date}</span>
                                                <StatusBadge status={item.status} />
                                            </div>
                                            <p className="font-semibold text-slate-900">{item.title}</p>
                                            {item.detail && <p className="text-sm text-slate-500">{item.detail}</p>}
                                            <p className="mt-1 text-xs text-slate-400">Pemohon: {item.requester_name ?? "-"}</p>
                                            <p className="mt-1 text-xs italic text-slate-400">&ldquo;{item.justification}&rdquo;</p>
                                        </div>
                                        <div className="flex shrink-0 gap-2">
                                            <button
                                                onClick={() => handleApproval(item, "approve")}
                                                disabled={processingId === item.id}
                                                className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                                            >
                                                {item.status === "APPROVED" ? "Tandai Selesai" : "Approve"}
                                            </button>
                                            {item.status !== "APPROVED" && (
                                                <button
                                                    onClick={() => handleApproval(item, "reject")}
                                                    disabled={processingId === item.id}
                                                    className="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:opacity-50"
                                                >
                                                    Reject
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* Riwayat Approval */}
                {activeTab === "approval_history" && canApprove && (
                    <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card">
                        <h3 className="text-base font-semibold text-slate-900">Riwayat Approval</h3>
                        <p className="mb-6 mt-1 text-sm text-slate-500">
                            Pengajuan yang sudah pernah kamu proses di tahapmu, lengkap sama keputusannya.
                        </p>

                        {!approvalHistory || approvalHistory.length === 0 ? (
                            <div className="flex flex-col items-center gap-2 py-14 text-center">
                                <IconClock className="h-8 w-8 text-slate-300" />
                                <p className="text-sm text-slate-500">Belum ada pengajuan yang pernah kamu proses.</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4">
                                {approvalHistory.map((item) => (
                                    <div
                                        key={`${item.type}-${item.id}`}
                                        className="rounded-xl border border-slate-200 p-4"
                                    >
                                        <div className="mb-1 flex flex-wrap items-center gap-2">
                                            <span
                                                className={`inline-flex items-center gap-1.5 text-xs font-semibold ${
                                                    item.type === "Hardware" ? "text-blue-600" : "text-violet-600"
                                                }`}
                                            >
                                                {item.type === "Hardware" ? (
                                                    <IconDevice className="h-3.5 w-3.5" />
                                                ) : (
                                                    <IconApp className="h-3.5 w-3.5" />
                                                )}
                                                {item.type}
                                            </span>
                                            <span className="text-xs font-medium text-slate-400">{item.request_date}</span>
                                            <StatusBadge status={item.status} />
                                            <span className="text-slate-300">&middot;</span>
                                            <DecisionBadge decision={item.your_decision} />
                                        </div>
                                        <p className="font-semibold text-slate-900">{item.title}</p>
                                        {item.detail && <p className="text-sm text-slate-500">{item.detail}</p>}
                                        <p className="mt-1 text-xs text-slate-400">Pemohon: {item.requester_name ?? "-"}</p>
                                        <p className="mt-1 text-xs italic text-slate-400">&ldquo;{item.justification}&rdquo;</p>

                                        {item.status === "REJECTED" && item.your_decision === "approved" && (
                                            <p className="mt-2 text-xs text-slate-400">
                                                Ditolak belakangan di tahap berikutnya
                                                {item.rejected_by_name ? ` oleh ${item.rejected_by_name}` : ""}.
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

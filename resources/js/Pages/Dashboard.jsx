import {
    IconApp,
    IconBriefcase,
    IconBuilding,
    IconCheckCheck,
    IconCheckCircle,
    IconClipboardList,
    IconClock,
    IconDevice,
    IconFile,
    IconHistory,
    IconInbox,
    IconMail,
    IconPlus,
    IconUser,
    IconXCircle,
} from '@/Components/Icons';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import HardwareForm from './Hardware';
import SoftwareForm from './Software';

// Label, warna, & ikon badge status - disamain sama App\Enums\RequestStatus
// di backend. `tone` nentuin pasangan warna pill-nya (lihat StatusPill).
const STATUS_META = {
    WAITING_FOR_HEAD_APPROVAL: {
        label: 'Menunggu Kepala Departemen',
        tone: 'warn',
        icon: IconClock,
    },
    WAITING_FOR_IT_APPROVAL: {
        label: 'Menunggu Tim IT',
        tone: 'warn',
        icon: IconClock,
    },
    WAITING_FOR_FINANCE_APPROVAL: {
        label: 'Menunggu Finance',
        tone: 'warn',
        icon: IconClock,
    },
    APPROVED: { label: 'Disetujui', tone: 'ok', icon: IconCheckCircle },
    COMPLETED: { label: 'Selesai', tone: 'done', icon: IconCheckCheck },
    REJECTED: { label: 'Ditolak', tone: 'bad', icon: IconXCircle },
};

// Keputusan approver sendiri terhadap satu pengajuan - disamain sama nilai
// 'your_decision' yang dihitung backend di DashboardController::approvalHistoryFor().
const DECISION_META = {
    approved: { label: 'Kamu Setujui', tone: 'ok', icon: IconCheckCircle },
    rejected: { label: 'Kamu Tolak', tone: 'bad', icon: IconXCircle },
    completed: {
        label: 'Kamu Tandai Selesai',
        tone: 'done',
        icon: IconCheckCheck,
    },
};

const TONE_CLASS = {
    warn: 'bg-warning-soft text-warning',
    ok: 'bg-success-soft text-success',
    bad: 'bg-danger-soft text-danger',
    done: 'bg-accent-soft text-accent-deep',
    neutral: 'bg-surface-sunken text-ink-muted',
};

const TABS = [
    { id: 'create', label: 'Buat Pengajuan', icon: IconPlus },
    { id: 'history', label: 'Riwayat Pengajuan', icon: IconFile },
    {
        id: 'approval',
        label: 'Approval',
        icon: IconCheckCheck,
        approverOnly: true,
    },
    {
        id: 'approval_history',
        label: 'Riwayat Approval',
        icon: IconHistory,
        approverOnly: true,
    },
];

function Pill({ tone = 'neutral', icon: Icon, children }) {
    return (
        <span
            className={`inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11.5px] font-bold ${
                TONE_CLASS[tone] ?? TONE_CLASS.neutral
            }`}
        >
            {Icon && <Icon className="h-3 w-3" />}
            {children}
        </span>
    );
}

function StatusPill({ status }) {
    const meta = STATUS_META[status] ?? { label: status, tone: 'neutral' };
    return (
        <Pill tone={meta.tone} icon={meta.icon}>
            {meta.label}
        </Pill>
    );
}

function DecisionPill({ decision }) {
    const meta = DECISION_META[decision] ?? { label: decision, tone: 'neutral' };
    return (
        <Pill tone={meta.tone} icon={meta.icon}>
            {meta.label}
        </Pill>
    );
}

function StatTile({ icon: Icon, label, value, tone }) {
    return (
        <div className="flex items-center gap-3.5 rounded-[18px] border border-line bg-surface px-5 py-[18px] shadow-card">
            <span
                className={`flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-[13px] ${TONE_CLASS[tone]}`}
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

function ProfileField({ icon: Icon, label, value }) {
    return (
        <div className="flex min-w-0 items-start gap-2.5">
            <Icon className="mt-[3px] h-4 w-4 shrink-0 text-ink-faint" />
            <div className="min-w-0">
                <div className="mb-0.5 text-[10.5px] font-bold uppercase tracking-[0.08em] text-ink-faint">
                    {label}
                </div>
                <div className="truncate text-[13.5px] font-semibold text-ink">
                    {value}
                </div>
            </div>
        </div>
    );
}

function TypeBadge({ type }) {
    const isHardware = type === 'Hardware';
    const Icon = isHardware ? IconDevice : IconApp;

    return (
        <span
            className={`inline-flex items-center gap-1.5 text-[11.5px] font-bold ${
                isHardware ? 'text-tag-blue' : 'text-tag-violet'
            }`}
        >
            <Icon className="h-3.5 w-3.5" />
            {type}
        </span>
    );
}

function TypeIcon({ type }) {
    const isHardware = type === 'Hardware';
    const Icon = isHardware ? IconDevice : IconApp;

    return (
        <span
            className={`flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[11px] border border-line ${
                isHardware
                    ? 'bg-tag-blue-soft text-tag-blue'
                    : 'bg-tag-violet-soft text-tag-violet'
            }`}
        >
            <Icon className="h-[18px] w-[18px]" />
        </span>
    );
}

function EmptyState({ icon: Icon, children }) {
    return (
        <div className="flex flex-col items-center gap-2.5 px-5 py-14 text-center">
            <Icon className="h-8 w-8 text-ink-faint" />
            <p className="text-[13px] text-ink-muted">{children}</p>
        </div>
    );
}

function PanelHeading({ title, description }) {
    return (
        <div className="mb-5">
            <h2 className="text-[15.5px] font-extrabold text-ink">{title}</h2>
            {description && (
                <p className="mt-0.5 text-[12.5px] text-ink-muted">
                    {description}
                </p>
            )}
        </div>
    );
}

function ChoiceCard({ icon: Icon, title, description, selected, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`flex items-start gap-3.5 rounded-2xl border-[1.5px] p-[18px] text-left transition hover:-translate-y-px hover:border-accent ${
                selected
                    ? 'border-accent bg-accent-soft'
                    : 'border-line bg-surface-sunken'
            }`}
        >
            <span
                className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-[13px] border bg-surface text-accent ${
                    selected ? 'border-accent' : 'border-line'
                }`}
            >
                <Icon className="h-[21px] w-[21px]" />
            </span>
            <span>
                <span className="block text-sm font-bold text-ink">
                    {title}
                </span>
                <span className="mt-0.5 block text-xs leading-relaxed text-ink-muted">
                    {description}
                </span>
            </span>
        </button>
    );
}

export default function Dashboard() {
    const {
        auth,
        flash,
        hardwareHistory,
        softwareHistory,
        pendingApprovals,
        approvalHistory,
        canApprove,
    } = usePage().props;
    const user = auth.user;
    const [onRequest, setRequest] = useState('');
    const [activeTab, setActiveTab] = useState('create');
    const [processingId, setProcessingId] = useState(null);

    const handleApproval = (item, action) => {
        const routeName = `request.${item.type.toLowerCase()}.${action}`;
        setProcessingId(item.id);
        router.post(
            route(routeName, item.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    };

    const allHistory = [...(hardwareHistory ?? []), ...(softwareHistory ?? [])];
    const waitingCount = allHistory.filter((r) =>
        r.status?.startsWith('WAITING_'),
    ).length;
    const completedCount = allHistory.filter(
        (r) => r.status === 'COMPLETED' || r.status === 'APPROVED',
    ).length;
    const rejectedCount = allHistory.filter(
        (r) => r.status === 'REJECTED',
    ).length;
    const pendingCount = pendingApprovals?.length ?? 0;

    const visibleTabs = TABS.filter((tab) => !tab.approverOnly || canApprove);

    return (
        <AuthenticatedLayout
            title="Dashboard Pengajuan"
            subtitle={`Selamat datang kembali, ${user.name?.split(' ')[0]}`}
        >
            <Head title="Pengajuan" />

            {flash?.success && (
                <div className="flex items-center gap-2.5 rounded-2xl bg-success-soft px-4 py-3.5 text-[13px] font-semibold text-success">
                    <IconCheckCircle className="h-[17px] w-[17px] shrink-0" />
                    {flash.success}
                </div>
            )}

            {canApprove && pendingCount > 0 && (
                <button
                    onClick={() => setActiveTab('approval')}
                    className="flex w-full items-center justify-between gap-4 rounded-2xl bg-warning-soft px-4 py-3.5 text-left transition hover:brightness-[0.98]"
                >
                    <span className="flex items-center gap-3">
                        <IconInbox className="h-[18px] w-[18px] shrink-0 text-warning" />
                        <span className="text-[13px] font-semibold text-warning">
                            Ada <strong>{pendingCount}</strong> pengajuan yang
                            menunggu approval kamu.
                        </span>
                    </span>
                    <span className="shrink-0 text-[13px] font-bold text-warning">
                        Lihat &rarr;
                    </span>
                </button>
            )}

            {/* Ringkasan angka */}
            <section className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatTile
                    icon={IconClipboardList}
                    tone="done"
                    label="Total Pengajuan"
                    value={allHistory.length}
                />
                <StatTile
                    icon={IconClock}
                    tone="warn"
                    label="Sedang Diproses"
                    value={waitingCount}
                />
                <StatTile
                    icon={IconCheckCircle}
                    tone="ok"
                    label="Disetujui / Selesai"
                    value={completedCount}
                />
                <StatTile
                    icon={IconXCircle}
                    tone="bad"
                    label="Ditolak"
                    value={rejectedCount}
                />
            </section>

            {/* Identitas ringkas pemohon */}
            <section className="grid grid-cols-1 gap-[18px] rounded-[18px] border border-line bg-surface px-[22px] py-[18px] shadow-card sm:grid-cols-2 lg:grid-cols-4">
                <ProfileField icon={IconUser} label="Nama" value={user.name} />
                <ProfileField
                    icon={IconMail}
                    label="Email"
                    value={user.email}
                />
                <ProfileField
                    icon={IconBuilding}
                    label="Perusahaan"
                    value={user.entity ?? '-'}
                />
                <ProfileField
                    icon={IconBriefcase}
                    label="Jabatan"
                    value={user.position ?? '-'}
                />
            </section>

            {/*
                Tab + kontennya dibungkus satu kartu biar tab yang aktif kelihatan
                nyambung ke area kontennya. Klik tab yang lagi aktif buat nutup
                lagi (balik ke tampilan ringkas).
            */}
            <section className="overflow-hidden rounded-[20px] border border-line bg-surface shadow-card">
                <div
                    className={`flex gap-1 overflow-x-auto bg-surface-sunken p-2.5 ${
                        activeTab ? 'border-b border-line' : ''
                    }`}
                >
                    {visibleTabs.map((tab) => {
                        const Icon = tab.icon;
                        const active = activeTab === tab.id;

                        return (
                            <button
                                key={tab.id}
                                onClick={() =>
                                    setActiveTab(active ? null : tab.id)
                                }
                                className={`flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 py-2.5 text-[13px] font-bold transition ${
                                    active
                                        ? 'bg-accent text-on-bright shadow-pop'
                                        : 'text-ink-muted hover:bg-surface hover:text-ink'
                                }`}
                            >
                                <Icon className="h-[15px] w-[15px]" />
                                {tab.label}
                                {tab.id === 'approval' && pendingCount > 0 && (
                                    <span className="ms-0.5 inline-flex h-[17px] min-w-[17px] items-center justify-center rounded-full bg-danger px-1 text-[10.5px] font-extrabold text-on-bright">
                                        {pendingCount}
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* ---------------------------- Buat Pengajuan --------------------------- */}
                {activeTab === 'create' && (
                    <div className="p-6 sm:px-7 sm:pb-7">
                        <PanelHeading
                            title="Pilih kategori pengajuan"
                            description="Pilih salah satu kategori di bawah untuk mulai mengisi form."
                        />

                        <div className="grid grid-cols-1 gap-3.5 md:grid-cols-2">
                            <ChoiceCard
                                icon={IconDevice}
                                title="Hardware"
                                description="Laptop, PC desktop, monitor, dan perangkat fisik lainnya."
                                selected={onRequest === 'HARDWARE'}
                                onClick={() => setRequest('HARDWARE')}
                            />
                            <ChoiceCard
                                icon={IconApp}
                                title="Software"
                                description="Lisensi aplikasi, langganan tools, dan kebutuhan digital."
                                selected={onRequest === 'SOFTWARE'}
                                onClick={() => setRequest('SOFTWARE')}
                            />
                        </div>

                        {onRequest === 'HARDWARE' && <HardwareForm user={user} />}
                        {onRequest === 'SOFTWARE' && <SoftwareForm user={user} />}
                    </div>
                )}

                {/* --------------------------- Riwayat Pengajuan -------------------------- */}
                {activeTab === 'history' && (
                    <div className="p-6 sm:px-7 sm:pb-7">
                        <PanelHeading
                            title="Riwayat pengajuan kamu"
                            description="Semua pengajuan hardware & software yang pernah kamu ajukan."
                        />

                        {allHistory.length === 0 ? (
                            <EmptyState icon={IconInbox}>
                                Kamu belum pernah mengajukan form request apapun.
                            </EmptyState>
                        ) : (
                            <div className="flex flex-col gap-2.5">
                                {hardwareHistory?.map((req) => (
                                    <div
                                        key={`hw-${req.id}`}
                                        className="flex flex-wrap items-center gap-3.5 rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5"
                                    >
                                        <TypeIcon type="Hardware" />
                                        <div className="min-w-0 flex-1">
                                            <div className="text-[13.5px] font-bold text-ink">
                                                {req.hardware_type}
                                            </div>
                                            <div className="mt-0.5 flex items-center gap-2 text-xs text-ink-muted">
                                                <TypeBadge type="Hardware" />
                                                <span className="text-ink-faint">
                                                    &middot;
                                                </span>
                                                {req.request_date}
                                            </div>
                                        </div>
                                        <StatusPill status={req.status} />
                                    </div>
                                ))}

                                {softwareHistory?.map((req) => (
                                    <div
                                        key={`sw-${req.id}`}
                                        className="flex flex-wrap items-center gap-3.5 rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5"
                                    >
                                        <TypeIcon type="Software" />
                                        <div className="min-w-0 flex-1">
                                            <div className="text-[13.5px] font-bold text-ink">
                                                {req.software_name}
                                            </div>
                                            <div className="mt-0.5 flex items-center gap-2 text-xs text-ink-muted">
                                                <TypeBadge type="Software" />
                                                <span className="text-ink-faint">
                                                    &middot;
                                                </span>
                                                {req.request_date}
                                            </div>
                                        </div>
                                        <StatusPill status={req.status} />
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* -------------------------------- Approval ------------------------------ */}
                {activeTab === 'approval' && canApprove && (
                    <div className="p-6 sm:px-7 sm:pb-7">
                        <PanelHeading
                            title="Menunggu approval kamu"
                            description="Setujui atau tolak pengajuan yang masuk ke tahap kamu."
                        />

                        {pendingCount === 0 ? (
                            <EmptyState icon={IconCheckCheck}>
                                Tidak ada pengajuan yang aktif saat ini.
                            </EmptyState>
                        ) : (
                            <div className="flex flex-col gap-2.5">
                                {pendingApprovals.map((item) => (
                                    <div
                                        key={`${item.type}-${item.id}`}
                                        className="flex flex-col gap-4 rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5 md:flex-row md:items-center"
                                    >
                                        <TypeIcon type={item.type} />

                                        <div className="min-w-0 flex-1">
                                            <div className="text-[13.5px] font-bold text-ink">
                                                {item.title}
                                            </div>
                                            {item.detail && (
                                                <div className="mt-0.5 text-xs text-ink-muted">
                                                    {item.detail}
                                                </div>
                                            )}
                                            <div className="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                                                <TypeBadge type={item.type} />
                                                <span className="text-ink-faint">
                                                    &middot;
                                                </span>
                                                {item.request_date}
                                                <StatusPill
                                                    status={item.status}
                                                />
                                            </div>
                                            <p className="mt-1.5 text-xs text-ink-faint">
                                                Diajukan oleh{' '}
                                                {item.requester_name ?? '-'}
                                            </p>
                                            {item.justification && (
                                                <p className="mt-1 text-xs italic text-ink-faint">
                                                    &ldquo;{item.justification}
                                                    &rdquo;
                                                </p>
                                            )}
                                        </div>

                                        <div className="flex shrink-0 gap-2">
                                            {item.status !== 'APPROVED' && (
                                                <button
                                                    onClick={() =>
                                                        handleApproval(
                                                            item,
                                                            'reject',
                                                        )
                                                    }
                                                    disabled={
                                                        processingId === item.id
                                                    }
                                                    className="rounded-[10px] border-[1.5px] border-danger px-3.5 py-2 text-xs font-bold text-danger transition hover:-translate-y-px disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    Tolak
                                                </button>
                                            )}
                                            <button
                                                onClick={() =>
                                                    handleApproval(
                                                        item,
                                                        'approve',
                                                    )
                                                }
                                                disabled={
                                                    processingId === item.id
                                                }
                                                className="rounded-[10px] bg-success px-3.5 py-2 text-xs font-bold text-on-bright transition hover:-translate-y-px disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                {item.status === 'APPROVED'
                                                    ? 'Tandai Selesai'
                                                    : 'Setujui'}
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}

                {/* ---------------------------- Riwayat Approval -------------------------- */}
                {activeTab === 'approval_history' && canApprove && (
                    <div className="p-6 sm:px-7 sm:pb-7">
                        <PanelHeading
                            title="Riwayat keputusan kamu"
                            description="Pengajuan yang sudah pernah kamu proses di tahapmu, lengkap sama keputusannya."
                        />

                        {!approvalHistory || approvalHistory.length === 0 ? (
                            <EmptyState icon={IconHistory}>
                                Belum ada pengajuan yang pernah kamu proses.
                            </EmptyState>
                        ) : (
                            <div className="flex flex-col gap-2.5">
                                {approvalHistory.map((item) => (
                                    <div
                                        key={`${item.type}-${item.id}`}
                                        className="flex flex-wrap items-start gap-3.5 rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5"
                                    >
                                        <TypeIcon type={item.type} />

                                        <div className="min-w-0 flex-1">
                                            <div className="text-[13.5px] font-bold text-ink">
                                                {item.title}
                                            </div>
                                            {item.detail && (
                                                <div className="mt-0.5 text-xs text-ink-muted">
                                                    {item.detail}
                                                </div>
                                            )}
                                            <div className="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                                                <TypeBadge type={item.type} />
                                                <span className="text-ink-faint">
                                                    &middot;
                                                </span>
                                                {item.request_date}
                                                <StatusPill
                                                    status={item.status}
                                                />
                                            </div>
                                            <p className="mt-1.5 text-xs text-ink-faint">
                                                Diajukan oleh{' '}
                                                {item.requester_name ?? '-'}
                                            </p>

                                            {item.status === 'REJECTED' &&
                                                item.your_decision ===
                                                    'approved' && (
                                                    <p className="mt-1.5 text-xs text-ink-faint">
                                                        Ditolak belakangan di
                                                        tahap berikutnya
                                                        {item.rejected_by_name
                                                            ? ` oleh ${item.rejected_by_name}`
                                                            : ''}
                                                        .
                                                    </p>
                                                )}
                                        </div>

                                        <DecisionPill
                                            decision={item.your_decision}
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </section>
        </AuthenticatedLayout>
    );
}

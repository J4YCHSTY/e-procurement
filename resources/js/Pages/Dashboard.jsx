import { useState } from "react";
import HardwareForm from "./Hardware";
import SoftwareForm from "./Software";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage, router } from "@inertiajs/react";

// Label & warna badge status, disamain sama App\Enums\RequestStatus di backend.
const STATUS_META = {
    WAITING_FOR_HEAD_APPROVAL: { label: "Menunggu Approval Kepala Departemen", className: "bg-yellow-100 text-yellow-800" },
    WAITING_FOR_IT_APPROVAL: { label: "Menunggu Approval Tim IT", className: "bg-yellow-100 text-yellow-800" },
    WAITING_FOR_FINANCE_APPROVAL: { label: "Menunggu Approval Finance", className: "bg-yellow-100 text-yellow-800" },
    APPROVED: { label: "Disetujui", className: "bg-green-100 text-green-800" },
    REJECTED: { label: "Ditolak", className: "bg-red-100 text-red-800" },
};

function StatusBadge({ status }) {
    const meta = STATUS_META[status] ?? { label: status, className: "bg-gray-100 text-gray-800" };
    return (
        <span className={`px-2 py-1 text-xs rounded-md font-semibold ${meta.className}`}>
            {meta.label}
        </span>
    );
}

export default function Dashboard() {
    const { auth, flash, hardwareHistory, softwareHistory, pendingApprovals, canApprove } = usePage().props;
    const user = auth.user;
    const [onRequest, setRequest] = useState("");
    const [requestCategory, setRequestCategory] = useState("");
    const [activeTab, setActiveTab] = useState("");
    const [processingId, setProcessingId] = useState(null);

    const handleApproval = (item, action) => {
        const routeName = `request.${item.type.toLowerCase()}.${action}`;
        setProcessingId(item.id);
        router.post(route(routeName, item.id), {}, {
            preserveScroll: true,
            onFinish: () => setProcessingId(null),
        });
    };

    return (
        <AuthenticatedLayout header={<h2 className="font-bold text-xl text-gray-800 loading-tight">PENGAJUAN</h2>}>
            <Head title="Pengajuan" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 className="text-lg font-bold text-gray-900 text-center m-4">INFORMASI USER</h3>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm bg-gray-50 p-4 rounded-md border">
                            <div><span className="text-gray-500 block text-xs uppercase font-bold">Nama</span>{user.name}</div>
                            <div><span className="text-gray-500 block text-xs uppercase font-bold">Email</span>{user.email}</div>
                            <div><span className="text-gray-500 block text-xs uppercase font-bold">Perusahaan</span>{user.entity}</div>
                            <div><span className="text-gray-500 block text-xs uppercase font-bold">Jabatan</span>{user.position}</div>
                        </div>
                    </div>
                    <div className="bg-white shadow-md sm-rounded-lg overflow-hidden">
                        <div className="flex">
                            <button onClick={() => setActiveTab('create')} className={`flex-1 py-4 px-6 text-center font-bold text-sm transition-colors ${activeTab === 'create' ? 'bg-black text-white' : 'text-black hover:bg-gray-50'}`}>
                                BUAT PENGAJUAN BARU
                            </button>
                            <button onClick={() => setActiveTab('history')} className={`flex-1 py-4 px-6 text-center font-bold text-sm transition-colors ${activeTab === 'history' ? 'bg-black text-white' : 'text-black hover:bg-gray-50'}`}>
                                RIWAYAT PENGAJUAN
                            </button>
                            {canApprove && (
                                <button onClick={() => setActiveTab('approval')} className={`flex-1 py-4 px-6 text-center font-bold text-sm transition-colors ${activeTab === 'approval' ? 'bg-black text-white' : 'text-black hover:bg-gray-50'}`}>
                                    PENYETUJUAN PENGAJUAN
                                    {pendingApprovals && pendingApprovals.length > 0 && (
                                        <span className="absolute top-2 right-4 bg-red-500 text-white text-xs font-bold px-2 py-0 5 rounded-full">{pendingApprovals.length}</span>
                                    )}
                                </button>
                            )}
                        </div>
                    </div>
                    {activeTab === 'create' && (
                        <div className="space-y-6 animate-fade-in-up">
                            <div className="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                                <label lassName="font-bold block text-sm text-gray-700 mb-2">
                                    PILIH KATEGORI PENGJUAN
                                </label>
                                <br></br>
                                <select className="border-gray-300 focus:border-indigo 600 focus:ring-indigo-500 rounded-sm shadow-sm w-full md:w-1/3" value={onRequest} onChange={(e) => setRequest(e.target.value)}>
                                    <option value="">-- Silahkan Pilih Kategori Pengajuan --</option>
                                    <option value="HARDWARE">HARDWARE</option>
                                    <option value="SOFTWARE">SOFTWARE</option>
                                </select>
                            </div>

                            {onRequest === "HARDWARE" && <HardwareForm user={user} />}
                            {onRequest === "SOFTWARE" && <SoftwareForm user={user} />}
                        </div>
                    )}
                    {activeTab === 'history' && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-8">
                            <h3 className="text-lg-font-bold text-gray-900 mb-4 border-b pb-2">
                                RIWAYAT PENGAJUAN
                            </h3>
                        {(!hardwareHistory || hardwareHistory.length === 0) && (!softwareHistory || softwareHistory.length === 0) ? (
                            <div className="text-center py-8 bg-gray-50 rounded-md">
                                <p className="text-gray-500 text-sm">
                                    USER BELUM PERNAH MENGAJUKAN FORM REQUEST APAPUN.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-center text-gray-500">
                                    <thead className="text-xs text-gray-700 uppercase bg-gray-100">
                                        <tr>
                                            <th className="px-6 py-4">Tanggal Pengajuan</th>
                                            <th className="px-6 py-4">Kategori Pengajuan</th>
                                            <th className="px-6 py-4">Spesifikasi / Nama Item</th>
                                            <th className="px-6 py-4">Status Pengajuan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {hardwareHistory && hardwareHistory.map((req) => (
                                            <tr key={`hw-${req.id}`}className="bg-white transition">
                                                <td className="px-6 py-4 font-medium text-gray-900">{req.request_date}</td>
                                                <td className="px-6 py-4"><span className="px-3 py-1 font-bold text-blue-500 rounded-full text-xs tracking-wide">HARDWARE</span></td>
                                                <td className="px-6 py-4 font-medium text-gray-900">{req.hardware_type}</td>
                                                <td className="px-6 py-4 font-medium"><StatusBadge status={req.status} /></td>
                                            </tr>
                                        ))}

                                        {softwareHistory && softwareHistory.map((req) => (
                                            <tr key={`sw-${req.id}`} className="bg-white transition">
                                                <td className="px-6 py-4 font-medium text-gray-900">{req.request_date}</td>
                                                <td className="px-6 py-4"><span className="px-3 py-1 font-bold text-green-500 rounded-full text-xs tracking-wide">SOFTWARE</span></td>
                                                <td className="px-6 py-4 font-medium text-gray-900">{req.software_name}</td>
                                                <td className="px-6 py-4"><StatusBadge status={req.status} /></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                    )}
                    {activeTab === 'approval' && canApprove && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 animate-fade-in-up">
                            <h3 className="text-lg font-bold text-gray-900 mb-2">DAFTAR REQUEST PENGAJUAN</h3>
                            <p className="text-sm text-gray-500 mb-6">APPROVE/REJECT</p>
                            {(!pendingApprovals || pendingApprovals.length === 0) ? (
                                <p className="text-gray-500 text-sm text-center py-8 bg-gray-50 rounded-md">
                                    Tidak Ada Pengajuan Yang Aktif
                                </p>
                            ) : (
                                <div className="grid grid-cols-1 gap-4">
                                    {pendingApprovals.map((item) => (
                                        <div key={`${item.type}-${item.id}`} className="flex flex-col md:flex-row justify-between md:items-center bg-white border rounded-lg shadow-sm hover:shadow-md transition p-4 gap-4">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2 mb-1">
                                                    <span className={`px-2 py-0.5 rounded text-[10px] font-bold text-white ${item.type === 'Hardware' ? 'bg-blue-500' : 'bg-green-500'}`}>
                                                        {item.type.toUpperCase()}
                                                    </span>
                                                    <span className="text-xs text-gray-500 font-medium">{item.request_date}</span>
                                                    <StatusBadge status={item.status} />
                                                </div>
                                                <p className="font-bold text-gray-900">{item.title}</p>
                                                {item.detail && <p className="text-sm text-gray-600">{item.detail}</p>}
                                                <p className="text-xs text-gray-500 mt-1">Pemohon: {item.requester_name ?? '-'}</p>
                                                <p className="text-xs text-gray-500 italic mt-1">"{item.justification}"</p>
                                            </div>
                                            <div className="flex gap-2 shrink-0">
                                                <button
                                                    onClick={() => handleApproval(item, 'approve')}
                                                    disabled={processingId === item.id}
                                                    className="px-4 py-2 text-sm font-bold text-white bg-green-600 rounded-md hover:bg-green-700 disabled:opacity-50"
                                                >
                                                    Approve
                                                </button>
                                                <button
                                                    onClick={() => handleApproval(item, 'reject')}
                                                    disabled={processingId === item.id}
                                                    className="px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50"
                                                >
                                                    Reject
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    )
}
import { useState } from "react";
import HardwareForm from "./Hardware";
import SoftwareForm from "./Software";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";

export default function Dashboard() {
    const { auth, flash, hardwareHistory, softwareHistory, pendingApprovals } = usePage().props;
    const user = auth.user;
    const [onRequest, setRequest] = useState("");
    const [requestCategory, setRequestCategory] = useState("");
    const [activeTab, setActiveTab] = useState("");
    const isManager = user.position.toLowerCase().includes("manager") || user.position.toLowerCase().includes("head");
    const handleApproval = (id, action) => {alert(`Simulasi: ${action} dengan pengajuan ID ${id}`)};

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
                            {isManager && (
                                <button onClick={() => setActiveTab('approval')} className={`flex-1 py-4 px-6 text-center font-bold text-sm transition-colors ${activeTab === 'approval' ? 'bg-black text-white' : 'text-black hover:bg-gray-50'}`}>
                                    PENYETUJUAN PENGAJUAN
                                    {pendingApprovals && pendingApprovals.length > 0 && (
                                        <span className="absolute top-2 right-4 bg-red-500 text-white text-xs font-bold px-2 py-0 5 rounded-full">{pendingApprovals.length}</span>
                                    )}
                                </button>
                            )}
                        </div>
                    </div>
                    <div className="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <label lassName="font-bold block text-sm text-gray-700 mb-2">
                            PILIH JENIS PENGAJUAN
                        </label>
                        <br></br>
                        <select className="border-gray-300 focus:border-indigo 600 focus:ring-indigo-500 rounded-sm shadow-sm w-full md:w-1/3" value={onRequest} onChange={(e) => setRequest(e.target.value)}>
                            <option value="">-- PILIH PENGAJUAN --</option>
                            <option value="HARDWARE">HARDWARE</option>
                            <option value="SOFTWARE">SOFTWARE</option>
                        </select>
                    </div>

                    {onRequest === "HARDWARE" && <HardwareForm />}
                    {onRequest === "SOFTWARE" && <SoftwareForm />}

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
                                                <td className="px-6 py-4 font-medium"><span className={`px-2 py-1 text-xs rounded-md font-semibold ${
                                                    req.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    req.status === 'approved' ? 'bg-green-100 text-green-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                    {req.status}
                                                </span></td>
                                            </tr>
                                        ))}

                                        {softwareHistory && softwareHistory.map((req) => (
                                            <tr key={`sw-${req.id}`} className="bg-white transition">
                                                <td className="px-6 py-4 font-medium text-gray-900">{req.request_date}</td>
                                                <td className="px-6 py-4"><span className="px-3 py-1 font-bold text-green-500 rounded-full text-xs tracking-wide">SOFTWARE</span></td>
                                                <td className="px-6 py-4 font-medium text-gray-900">{req.software_name}</td>
                                                <td className="px-6 py-4"><span className={`px-2 py-1 text-xs rounded-md font-semibold ${
                                                    req.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' :
                                                    req.status === 'approved' ? 'bg-green-100 text-green-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>{req.status}</span></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    )
}
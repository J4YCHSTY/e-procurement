import { useState } from "react";
import HardwareForm from "./Hardware";
import SoftwareForm from "./Software";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";

export default function Dashboard() {
    const user = usePage().props.auth.user;
    const [onRequest, setRequest] = useState("");
    const { flash } = usePage().props;

    return (
        <AuthenticatedLayout header={<h2 className="font-bold text-xl text-gray-800 loading-tight">PENGAJUAN</h2>}>
            <Head title="Pengajuan" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p6">
                        <h3 className="text-lg font-bold text-gray-900 text-center m-4">INFORMASI PENGAJU</h3>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm bg-gray-50 p-4 rounded-md border">
                            <div><span className="text-gray-500 block text-xs uppercase font-bold">Nama</span>{user.name}</div>
                            <div><span className="text-gray-500 block text-xs uppercase font-bold">Email</span>{user.email}</div>
                            <div><span className="text-gray-500 block text-xs uppercase font-bold">Perusahaan</span>{user.entity}</div>
                            <div><span className="text-gray-500 block text-xs uppercase font-bold">Jabatan</span>{user.position}</div>
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
                </div>
            </div>
        </AuthenticatedLayout>
    )
}
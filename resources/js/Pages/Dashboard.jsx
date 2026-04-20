import { useState } from "react";
import HardwareForm from "./Hardware";
import SoftwareForm from "./Software";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";

export default function Dashboard() {
    const user = usePage().props.auth.user;
    const [Request, setRequest] = useState("");

    return (
        <AuthenticatedLayout header={<h2 className="font-bold text-xl text-gray-800 loading-tight">PENGAJUAN</h2>}>
            <Head title="Pengajuan" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <label className="block font-bold text-sm text-gray-700 mb-2">Pilih kategori pengajuan</label>
                        <select className="border-gray-300 focus:border-navy-500 focus-ring-navy-500 rounded-md shadow-md w-full md:w-1/3" value={Request} onChange={(e) => setRequest(e.target.value)}>
                            <option value="">PILIH KATEGORI PENGAJUAN</option>
                            <option value="HARDWARE">HARDWARE</option>
                            <option value="SOFTWARE">SOFTWARE</option>
                        </select>
                    </div>

                    {Request === "HARDWARE" && <HardwareForm />}
                    {Request === "SOFTWARE" && <SoftwareForm />}
                </div>
            </div>
        </AuthenticatedLayout>
    )
}
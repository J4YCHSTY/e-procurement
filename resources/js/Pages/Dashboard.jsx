import { useState } from "react";
import HardwareForm from "./Hardware";
import SoftwareForm from "./Software";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";

export default function Dashboard() {
    const user = usePage().props.auth.user;
    const [request, setRequest] = useState("");

    return (
        <AuthenticatedLayout header={<h2 className="font-bold text-xl text-gray-800 loading-tight">PENGAJUAN</h2>}>
            <Head title="Pengajuan" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <HardwareForm />
                    <SoftwareForm />
                </div>
            </div>
        </AuthenticatedLayout>
    )
}
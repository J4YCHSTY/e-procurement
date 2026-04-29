import { useState } from 'react';
import { useForm } from '@inertiajs/react';

const SoftwareForm = () =>  {
    const today = new Date().toISOString().split('T')[0];
    const {data, setData, post, processing, errors} = useForm({
        request_date: today,
        software_name: "",
        software_type: "",
        license_count: "",
        duration_months: "",
        estimated_cost: "",
        justification: "",
        digital_signature: false
    });
    const [requestDate, setRequestDate] = useState(today);
    const [softwareType, setsoftwareType] = useState("");
    const [softwareUsage, setSoftwareUsage] = useState("");
    const [digitalSignature, setDigitalSignature] = useState(false);
    const submitSoftware = (e) => {
        e.preventDefault();
        post(route('request.software.store'))
    }

    return (
        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-gray-600 mt-6 animate-fade-in-up">
            <h3 className="text-lg font-bold text-gray-900 mb-4">FORM PENGAJUAN SOFTWARE</h3>
            <form className="space-y-4" onSubmit={submitSoftware}>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="pb-4">
                        <label className="block font-medium text-sm text-gray-700 mb-2">TANGGAL PERMINTAAN <span className="text-red-500">*</span></label>
                        <input type="date" className="border-gray-300 bg-gray-50 focus:border-gray-500 focus:ring-gray-500 rounded-md shadow-md w-full" value={data.request_date} onChange={(e) => setData('request_date', e.target.value)} required/>
                    </div>
                    <div className="pb-4">
                        <label className="block font-medium text-sm text-gray-700 mb-2">Nama Aplikasi / Software <span className="text-red-500">*</span></label>
                        <input type="text" className="border-gray-300 focus:border-gray-500 focus:ring-gray-500 rounded-md shadow-sm w-full" value={data.software_name} onChange={(e) => setData('software_name', e.target.value)} required/>
                    </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="pb-4">
                        <label className="block font-medium text-sm mb-2">Pilih Jenis Software Yang Dibutuhkan</label>
                        <select className="border-gray-600 focus:border-gray-500 focus:ring-gray-300 rounded-md shadow-sm w-full" value={data.software_type} onChange={(e) => setData('software_type', e.target.value)}>
                            <option value="">-- PILIH SOFTWARE --</option>
                            <option value="Subscription">SUBSCRIPTION</option>
                            <option value="License">LICENSE</option>
                            <option value="Software">ADDON</option>
                        </select>
                    </div>
                    <div className="pb-4">
                        <label className="block font-medium text-sm mb-2">Penggunaan Aplikasi / Software <span className="text-red-500">*</span></label>
                        <select className="border-gray-600 focus:border-gray-500 focus:ring-gray-300 rounded-md shadow-sm w-full" value={data.software_usage} onChange={(e) => setData('software_usage', e.target.value)} required>
                            <option value="">-- PILIH PENGGUNAAN --</option>
                            <option value="individu">INDIVIDU</option>
                            <option value="team">TEAM</option>
                        </select>
                    </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 bg-gray-50 p-4 gap-6 border rounded-md">
                    <div className="pb-4">
                        <label className="block font-medium text-sm text-gray-700 mb-2">Jumlah Lisensi / User <span className="text-red-500">*</span></label>
                        <input type="number" min="1" defaultValue="1" className="border-gray-300 focus:border-gray-600 focus:ring-gray-500 rounded-md shadow-sm w-full" value={data.license_count} onChange={(e) => setData('license_count', e.target.value)} required/>
                    </div>
                    <div className="pb-4">
                        <label className="block font-medium text-sm text-gray-700 mb-2">Durasi (Bulan) <span className="text-red-500">*</span></label>
                        <input type="number" min="1" placeholder="Contoh: 12 (untuk 12 bulan)" className="border-gray-300 focus:border-gray-600 focus:ring-gray-500 rounded-md shadow-sm w-full" value={data.duration_months} onChange={(e) => setData('duration_months', e.target.value)} disabled={data.software_type === 'License'}/>
                        <p className="text-xs text-gray-500 mt-1"><span className="text-red-500">* Kosongkan jika tidak ada durasi spesifik</span></p>
                    </div>
                    <div className="pb-4">
                        <label className="block font-medium text-sm text-gray-700 mb-2">Total Biaya (Rp) <span className="text-red-500">*</span></label>
                        <input type="number" placeholder="Contoh: 1000000 (untuk Rp. 1.000.000,00)" className="border-gray-300 focus:border-gray-600 focus:ring-gray-500 rounded-md shadow-sm w-full" value={data.estimated_cost} onChange={(e) => setData('estimated_cost', e.target.value)} required/>
                    </div>
                </div>
                <div>
                    <label className="block font-medium text-sm text-gray-600 mb-4">
                        Alasan Pengajuan & Jelaskan Spesifikasi Khusus (Jika Ada) <span className="text-red-500">*</span>
                    </label>
                    <textarea className="border-gray-400 focus:border-gray-500 focus:ring-gray-700 rounded-md shadow-md w-full" placeholder="Masukan alasan pengajuan" value={data.justification} onChange={(e) => setData('justification', e.target.value)} required></textarea>
                </div>
                <div className="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-md flex items-start gap-3">
                    <input type="checkbox" className="w-4 h-4 text-gray-600 bg-gray-100 border-gray-400 rounded focus:ring-gray-500" checked={data.digital_signature} onChange={(e) => setData('digital_signature', e.target.checked)} required/>
                    <label className="font-medium text-gray-900 cursor-pointer">
                        Dengan mencentang ini, saya menyatakan bahwa informasi yang saya berikan sudah benar!
                    </label>
                </div>
                <div className="flex justify-end pt-4 border-t mt-6">
                    <button type="submit" disabled={!data.digital_signature || processing} className={`p-4 bg-black text-white font-bold rounded-md hover:bg-white hover:text-gray-600 transition-duration-150 ease-in-out shadow-md ${data.digital_signature && !processing ? 'bg-black text-white hover:bg-gray-800 cursor-pointer' : 'bg-gray-300 text-gray-500 cursor-not-allowed'}`} > {processing ? "Submitting..." : "Create Form & Send"}</button>
                </div>
            </form>
        </div>
    )
}

export default SoftwareForm;
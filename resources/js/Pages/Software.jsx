import { useState } from 'react';

const SoftwareForm = () =>  {
    const today = new Date().toISOString().split('T')[0];
    const [requestDate, setRequestDate] = useState(today);
    const [softwareType, setsoftwareType] = useState("");
    const [softwareUsage, setSoftwareUsage] = useState("");
    const [digitalSignature, setDigitalSignature] = useState(false);

    return (
        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-gray-600 mt-6 animate-fade-in-up">
            <h3 className="text-lg font-bold text-gray-900 mb-4">FORM PENGAJUAN SOFTWARE</h3>
            <form className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="pb-4">
                        <label className="block font-medium text-sm text-gray-700 mb-2">TANGGAL PERMINTAAN <span className="text-red-500">*</span></label>
                        <input type="date" className="border-gray-300 bg-gray-50 focus:border-gray-500 focus:ring-gray-500 rounded-md shadow-md w-full" value={requestDate} onChange={(e) => setRequestDate(e.target.value)} required/>
                    </div>
                    <div className="pb-4">
                        <label className="block font-medium text-sm text-gray-700 mb-2">Nama Aplikasi / Software <span className="text-red-500">*</span></label>
                        <input type="text" className="border-gray-300 focus:border-gray-500 focus:ring-gray-500 rounded-md shadow-sm w-full" required/>
                    </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="pb-4">
                        <label className="block font-medium text-sm mb-2">Pilih Jenis Software Yang Dibutuhkan</label>
                        <select className="border-gray-600 focus:border-gray-500 focus:ring-gray-300 rounded-md shadow-sm w-full" value={softwareType} onChange={(e) => setsoftwareType(e.target.value)}>
                            <option value="">-- PILIH SOFTWARE --</option>
                            <option value="Subscription">SUBSCRIPTION</option>
                            <option value="License">LICENSE</option>
                            <option value="Software">ADDON</option>
                        </select>
                    </div>
                    <div className="pb-4">
                        <label className="block font-medium text-sm mb-2">Penggunaan Aplikasi / Software <span className="text-red-500">*</span></label>
                        <select className="border-gray-600 focus:border-gray-500 focus:ring-gray-300 rounded-md shadow-sm w-full" value={softwareUsage} onChange={(e) => setSoftwareUsage(e.target.value)} required>
                            <option value="">-- PILIH PENGGUNAAN --</option>
                            <option value="individu">INDIVIDU</option>
                            <option value="team">TEAM</option>
                        </select>
                    </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-3 bg-gray-50 p-4 gap-6 border rounded-md">
                    <div className="pb-4">
                        <label className="block font-medium text-sm text-gray-700 mb-2">Jumlah Lisensi / User <span className="text-red-500">*</span></label>
                        
                    </div>
                    <div className="pb-4">
                        
                    </div>
                </div>
                <div>
                    <label className="block font-medium text-sm text-gray-600 mb-4">
                        Alasan Pengajuan & Jelaskan Spesifikasi Khusus (Jika Ada) <span className="text-red-500">*</span>
                    </label>
                    <textarea className="border-gray-400 focus:border-gray-500 focus:ring-gray-700 rounded-md shadow-md w-full" placholder="Masukan alasan pengajuan" required></textarea>
                </div>
                <div className="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-md flex items-start gap-3">
                    <input type="checkbox" className="w-4 h-4 text-gray-600 bg-gray-100 border-gray-400 rounded focus:ring-gray-500" checked={digitalSignature} onChange={(e) => setDigitalSignature(e.target.checked)} required/>
                    <label className="font-medium text-gray-900 cursor-pointer">
                        Dengan mencentang ini, saya menyatakan bahwa informasi yang saya berikan sudah benar!
                    </label>
                </div>
                <div className="flex justify-end pt-4 border-t mt-6">
                    <button className="p-4 bg-black text-white font-bold rounded-md hover:bg-white hover:text-gray-600 transition-duration-150 ease-in-out shadow-md">
                    Create Form & Send. 
                    </button>
                </div>
            </form>
        </div>
    )
}

export default SoftwareForm;
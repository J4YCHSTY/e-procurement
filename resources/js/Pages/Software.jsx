import { useState } from 'react';

const SoftwareForm = () =>  {
    const [softwareType, setsoftwareType] = useState("");

    return (
        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-gray-600 mt-6 animate-fade-in-up">
            <div>
                <label className="block font-medium text-sm mb-2">Pilih Jenis Software Yang Dibutuhkan</label>
                <select className="border-gray-600 focus:border-gray-500 focus:ring-gray-300 rounded-md shadow-sm w-full md:w-1/2" value={softwareType} onChange={(e) => setsoftwareType(e.target.value)}>
                    <option value="">-- PILIH SOFTWARE --</option>
                    <option value="Subscription">SUBSCRIPTION</option>
                    <option value="License">LICENSE</option>
                    <option value="Software">SOFTWARE</option>
                </select>
            </div>
            <div>
                <label className="block font-medium text-sm text-gray-600 mb-4">
                    Alasan Pengajuan & Jelaskan Spesifikasi Khusus (Jika Ada) <span className="text-red-500">*</span>
                </label>
                <textarea className="border-gray-400 focus:border-gray-500 focus:ring-gray-700 rounded-md shadow-md w-full"></textarea>
            </div>
        </div>
    )
}

export default SoftwareForm;
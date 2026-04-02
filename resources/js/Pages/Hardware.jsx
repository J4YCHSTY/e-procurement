import { useState } from "react";

const HardwareForm = () => {
    const [deviceType, setDeviceType] = useState("");

    return (
        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-gray-600 mt-6 animate-fade-in-up">
            <div>
                <label className="block font-medium text-sm mb-2">
                    Pilih Jenis Perangkat Yang Dibutuhkan <span className="text-red-600">*</span>
                </label>
                <select 
                    className="border-gray-600 focus:border-grey-500 focus:ring-grey-300 rounded-md shadow-sm w-full md:w-1/2"
                    value="{deviceType}"
                    onChange = {(e) => setDeviceType(e.target.value)}
                    required
                >
                    <option value="">-- Pilih Jenis Perangkat --</option>
                    <option value="Laptop">Laptop</option>
                    <option value="Desktop">Desktop</option>
                    <option value="Tablet">Gadget</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div>
                <label className="block font-medium text-sm text-gray-600 mb-4">
                    Alasan Pengajuan & Jelaskan Spesifikasi Khusus (Jika Ada) <span className="text-red-500">*</span>
                </label>
                <textarea 
                    className="border-gray-400 focus:border-gray-500 focus:ring-gray-700 rounded-md shadow-md w-full"
                    rows="4"
                    placholder="Masukan alasan pengajuan dan spesifikasi khusus (jika diperlukan)"
                    required
                ></textarea>
            </div>
            <div className="flex justify-end pt-4 border-t mt-6">
                <button type="button" className="px-6 py-6 bg-grey-600 font-bold rounded-md hover:bg-white hover:text-grey-400 transition-duration-150 ease-in-out shadow-md">
                    Create Form & Send. 
                </button>
            </div>
        </div>
    )
}

export default HardwareForm;
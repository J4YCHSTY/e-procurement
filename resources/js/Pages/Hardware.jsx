import { useState } from "react";

const HardwareForm = () => {
    const [deviceType, setDeviceType] = useState("");
    const [selectedRecommendation, setSelectedRecommendation] = useState("");
    const today = new Date().toISOString().split('T')[0];
    const [requestDate, setRequestDate] = useState(today);
    const [digitalSignature, setDigitalSignature] = useState(false);
    const recommendedDevices = {
        laptop: [
            { id: 'l1', name: 'Lenovo ThinkPad T14 (Standard Office)' },
            { id: 'l2', name: 'MacBook Pro 14 M3 (Design/Engineering)' },
            { id: 'l3', name: 'Dell Latitude 3420 (Entry Level)' }
        ],
        pc_desktop: [
            { id: 'p1', name: 'Dell OptiPlex 7000 (Standard Desk)' },
            { id: 'p2', name: 'Custom Build i9/RTX 4080 (Heavy Rendering)' }
        ],
        monitor: [
            { id: 'm1', name: 'Dell Ultrasharp 24 Inch' },
            { id: 'm2', name: 'LG 27 Inch 4K Monitor' }
        ]
    };
    const currentRecommendations = recommendedDevices[deviceType] || [];


    return (
        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-gray-600 mt-6 animate-fade-in-up">
            <h3 className="text-lg font-bold text-gray-900 mb-4">FORM PENGAJUAN HARDWARE</h3>
            <form className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="pb-4">
                        <label className="block font-medium text-sm text-gray-700 mb-2">TANGGAL PERMINTAAN <span className="text-red-500">*</span></label>
                        <input
                            type="date"
                            className="border-gray-300 bg-gray-50 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm w-full"
                            value={requestDate}
                            onChange={(e) => setRequestDate(e.target.value)}
                            required
                        />

                    </div>
                    <div className="pb-4">
                        <label className="block font-medium text-sm mb-2">
                            Pilih Jenis Perangkat Yang Dibutuhkan <span className="text-red-600">*</span>
                        </label>
                        <select 
                            className="border-gray-600 focus:border-grey-500 focus:ring-grey-300 rounded-md shadow-sm w-full md:w-1/2"
                            value={deviceType}
                            onChange = {(e) => {setDeviceType(e.target.value); setSelectedRecommendation('')}}
                            required
                        >
                            <option value="">-- Pilih Jenis Perangkat --</option>
                            <option value="laptop">Laptop</option>
                            <option value="pc_desktop">Desktop</option>
                            <option value="peripherals">peripherals (Headphone, Mouse, Keyboard)</option>
                            <option value="gadget">Gadget</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    {currentRecommendations.length > 0 && (
                        <div className="p-4 bg-gray-50 border border-gray-200 rounded-md mt-4 transition-all duration-300">
                            <label className="block font-medium text-sm text-black-800 mb-1">
                                Rekomendasi Perangkat Standar Perusahaan <span className="text-red-500">*</span>
                            </label>
                            <p className="text-xs text-black-600 mb-3">
                                Memilih spesifikasi dari standar perusahaan akan mempercepat proses persetujuan pengajuan Anda.
                            </p>
                            <select className="border-gray-300 focus:border-gray-500 rounded-md shadow-sm w-full md:w-2/3" value={selectedRecommendation} onChange={(e) => setSelectedRecommendation(e.target.value)} required>
                                <option value="">-- Pilih Spesifikasi Standar IT --</option>
                                {currentRecommendations.map((device) => (
                                    <option key={device.id} value={device.id}>
                                        {device.name}
                                    </option>
                                ))}
                                <option value="custom" className="font-semibold text-gray-500 italic">
                                    Ajukan Perangkat Lain (Di luar standar)
                                </option>
                            </select>
                        </div>
                    )}
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
                        <button type="button" className="p-4 bg-black text-white font-bold rounded-md hover:bg-white hover:text-gray-600 transition-duration-150 ease-in-out shadow-md">
                            Create Form & Send. 
                        </button>
                    </div>
                </div>
            </form>
        </div>
    )
}

export default HardwareForm;
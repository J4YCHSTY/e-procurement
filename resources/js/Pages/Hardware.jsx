import { useForm } from '@inertiajs/react';

const HardwareForm = () => {
    const today = new Date().toISOString().split('T')[0];
    const { data, setData, post, processing, errors} = useForm({
        request_date: today,
        hardware_type: "",
        hardware_recommendation: "",
        justification: "",
        digital_signature: false
    });
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
    const currentRecommendations = recommendedDevices[data.hardware_type] || [];
    const submitHardware = (e) => {
        e.preventDefault();
        post(route('request.hardware.store'))
    }

    const inputClass = "block w-full rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500";
    const labelClass = "mb-2 block text-sm font-medium text-slate-700";

    return (
        <div className="border-t border-slate-100 pt-6">
            <h3 className="mb-1 text-base font-semibold text-slate-900">Form Pengajuan Hardware</h3>
            <p className="mb-6 text-sm text-slate-500">Lengkapi detail perangkat yang kamu butuhkan.</p>
            <form className="space-y-5" onSubmit={submitHardware}>
                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label className={labelClass}>Tanggal Permintaan <span className="text-rose-500">*</span></label>
                        <input type="date" className={inputClass} value={data.request_date} onChange={(e) => setData('request_date', e.target.value)} required/>
                    </div>
                    <div>
                        <label className={labelClass}>
                            Pilih Jenis Perangkat Yang Dibutuhkan <span className="text-rose-500">*</span>
                        </label>
                        <select className={inputClass} value={data.hardware_type} onChange = {(e) => {setData('hardware_type', e.target.value); setData('hardware_recommendation', '')}} required>
                            <option value="">-- Pilih Jenis Perangkat --</option>
                            <option value="laptop">Laptop</option>
                            <option value="pc_desktop">Desktop</option>
                            <option value="peripherals">Peripherals (Headphone, Mouse, Keyboard)</option>
                            <option value="gadget">Gadget</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                {currentRecommendations.length > 0 && (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <label className="mb-1 block text-sm font-medium text-slate-700">
                            Rekomendasi Perangkat Standar Perusahaan <span className="text-rose-500">*</span>
                        </label>
                        <p className="mb-3 text-xs text-slate-500">
                            Memilih spesifikasi dari standar perusahaan akan mempercepat proses persetujuan pengajuan Anda.
                        </p>
                        <select className={inputClass} value={data.hardware_recommendation} onChange={(e) => setData('hardware_recommendation', e.target.value)} required>
                            <option value="">-- Pilih Spesifikasi Standar IT --</option>
                            {currentRecommendations.map((device) => (
                                <option key={device.id} value={device.id}>
                                    {device.name}
                                </option>
                            ))}
                            <option value="custom" className="font-semibold italic text-slate-500">
                                Ajukan Perangkat Lain (Di luar standar)
                            </option>
                        </select>
                    </div>
                )}
                <div>
                    <label className={labelClass}>
                        Alasan Pengajuan &amp; Jelaskan Spesifikasi Khusus (Jika Ada) <span className="text-rose-500">*</span>
                    </label>
                    <textarea className={inputClass} rows="4" placeholder="Masukan alasan pengajuan dan spesifikasi khusus (jika diperlukan)" value={data.justification} onChange={(e) => setData("justification", e.target.value)} required></textarea>
                </div>
                <div className="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <input id="digital-signature" type="checkbox" className="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={data.digital_signature} onChange={(e) => setData("digital_signature", e.target.checked)}/>
                    <label htmlFor="digital-signature" className="cursor-pointer text-sm font-medium text-slate-700">
                        Dengan mencentang ini, saya menyatakan bahwa informasi yang saya berikan sudah benar!
                    </label>
                    {errors.digital_signature && <p className="mt-1 text-sm text-rose-600">{errors.digital_signature}</p>}
                </div>
                <div className="flex justify-end border-t border-slate-100 pt-5">
                    <button type="submit" disabled={!data.digital_signature || processing} className={`rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm transition duration-150 ease-in-out ${data.digital_signature && !processing ? 'bg-brand-600 text-white hover:bg-brand-700 cursor-pointer' : 'cursor-not-allowed bg-slate-200 text-slate-400'}`}> {processing ? "Mengirim..." : "Kirim Pengajuan"}</button>
                </div>
            </form>
        </div>
    )
}

export default HardwareForm;

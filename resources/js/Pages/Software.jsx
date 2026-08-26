import { useForm } from '@inertiajs/react';

const SoftwareForm = () =>  {
    const today = new Date().toISOString().split('T')[0];
    const {data, setData, post, processing, errors} = useForm({
        request_date: today,
        software_name: "",
        software_type: "",
        software_usage: "",
        license_count: "",
        duration_months: "",
        estimated_cost: "",
        justification: "",
        digital_signature: false
    });
    const submitSoftware = (e) => {
        e.preventDefault();
        post(route('request.software.store'))
    }

    const inputClass = "block w-full rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-100 disabled:text-slate-400";
    const labelClass = "mb-2 block text-sm font-medium text-slate-700";

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-card">
            <h3 className="mb-1 text-base font-semibold text-slate-900">Form Pengajuan Software</h3>
            <p className="mb-6 text-sm text-slate-500">Lengkapi detail software/lisensi yang kamu butuhkan.</p>
            <form className="space-y-5" onSubmit={submitSoftware}>
                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label className={labelClass}>Tanggal Permintaan <span className="text-rose-500">*</span></label>
                        <input type="date" className={inputClass} value={data.request_date} onChange={(e) => setData('request_date', e.target.value)} required/>
                    </div>
                    <div>
                        <label className={labelClass}>Nama Aplikasi / Software <span className="text-rose-500">*</span></label>
                        <input type="text" className={inputClass} value={data.software_name} onChange={(e) => setData('software_name', e.target.value)} required/>
                    </div>
                </div>
                <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label className={labelClass}>Pilih Jenis Software Yang Dibutuhkan <span className="text-rose-500">*</span></label>
                        <select className={inputClass} value={data.software_type} onChange={(e) => {setData('software_type', e.target.value); if (e.target.value === 'License') {setData('duration_months', '')}}} required>
                            <option value="">-- Pilih Jenis Software --</option>
                            <option value="Subscription">Subscription</option>
                            <option value="License">License</option>
                            <option value="Software">Addon</option>
                        </select>
                    </div>
                    <div>
                        <label className={labelClass}>Penggunaan Aplikasi / Software <span className="text-rose-500">*</span></label>
                        <select className={inputClass} value={data.software_usage} onChange={(e) => setData('software_usage', e.target.value)} required>
                            <option value="">-- Pilih Penggunaan --</option>
                            <option value="individu">Individu</option>
                            <option value="team">Team</option>
                        </select>
                    </div>
                </div>
                <div className="grid grid-cols-1 gap-5 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-3">
                    <div>
                        <label className={labelClass}>Jumlah Lisensi / User <span className="text-rose-500">*</span></label>
                        <input type="number" min="1" className={inputClass} value={data.license_count} onChange={(e) => setData('license_count', e.target.value)} required/>
                    </div>
                    <div>
                        <label className={labelClass}>Durasi (Bulan) <span className="text-rose-500">*</span></label>
                        <input type="number" min="1" placeholder="Contoh: 12 (untuk 12 bulan)" className={inputClass} value={data.duration_months} onChange={(e) => setData('duration_months', e.target.value)} disabled={data.software_type === 'License'}/>
                        <p className="mt-1.5 text-xs text-slate-500">Kosongkan kalau jenisnya License (gak butuh durasi).</p>
                    </div>
                    <div>
                        <label className={labelClass}>Total Biaya (Rp) <span className="text-rose-500">*</span></label>
                        <input type="number" placeholder="Contoh: 1000000" className={inputClass} value={data.estimated_cost} onChange={(e) => setData('estimated_cost', e.target.value)} required/>
                    </div>
                </div>
                <div>
                    <label className={labelClass}>
                        Alasan Pengajuan &amp; Jelaskan Spesifikasi Khusus (Jika Ada) <span className="text-rose-500">*</span>
                    </label>
                    <textarea className={inputClass} rows="4" placeholder="Masukan alasan pengajuan" value={data.justification} onChange={(e) => setData('justification', e.target.value)} required></textarea>
                </div>
                <div className="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <input id="digital-signature-software" type="checkbox" className="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" checked={data.digital_signature} onChange={(e) => setData('digital_signature', e.target.checked)} required/>
                    <label htmlFor="digital-signature-software" className="cursor-pointer text-sm font-medium text-slate-700">
                        Dengan mencentang ini, saya menyatakan bahwa informasi yang saya berikan sudah benar!
                    </label>
                </div>
                <div className="flex justify-end border-t border-slate-100 pt-5">
                    <button type="submit" disabled={!data.digital_signature || processing} className={`rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm transition duration-150 ease-in-out ${data.digital_signature && !processing ? 'bg-brand-600 text-white hover:bg-brand-700 cursor-pointer' : 'cursor-not-allowed bg-slate-200 text-slate-400'}`}> {processing ? "Mengirim..." : "Kirim Pengajuan"}</button>
                </div>
            </form>
        </div>
    )
}

export default SoftwareForm;

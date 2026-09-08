import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { useForm } from '@inertiajs/react';

const SoftwareForm = () => {
    const today = new Date().toISOString().split('T')[0];
    const { data, setData, post, processing, errors } = useForm({
        request_date: today,
        software_name: '',
        software_type: '',
        software_usage: '',
        license_count: '',
        duration_months: '',
        estimated_cost: '',
        justification: '',
        digital_signature: false,
    });
    const submitSoftware = (e) => {
        e.preventDefault();
        post(route('request.software.store'));
    };

    return (
        <div className="mt-[22px] border-t border-line pt-[22px]">
            <h3 className="text-[15.5px] font-extrabold text-ink">
                Form Pengajuan Software
            </h3>
            <p className="mb-5 mt-0.5 text-[12.5px] text-ink-muted">
                Lengkapi detail software/lisensi yang kamu butuhkan.
            </p>

            <form className="space-y-4" onSubmit={submitSoftware}>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label className="form-label">
                            Tanggal Permintaan{' '}
                            <span className="text-danger">*</span>
                        </label>
                        <input
                            type="date"
                            className="form-field"
                            value={data.request_date}
                            onChange={(e) =>
                                setData('request_date', e.target.value)
                            }
                            required
                        />
                        <InputError message={errors.request_date} />
                    </div>
                    <div>
                        <label className="form-label">
                            Nama Aplikasi / Software{' '}
                            <span className="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            className="form-field"
                            value={data.software_name}
                            onChange={(e) =>
                                setData('software_name', e.target.value)
                            }
                            required
                        />
                        <InputError message={errors.software_name} />
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label className="form-label">
                            Pilih Jenis Software Yang Dibutuhkan{' '}
                            <span className="text-danger">*</span>
                        </label>
                        <select
                            className="form-field"
                            value={data.software_type}
                            onChange={(e) => {
                                setData('software_type', e.target.value);
                                if (e.target.value === 'License') {
                                    setData('duration_months', '');
                                }
                            }}
                            required
                        >
                            <option value="">-- Pilih Jenis Software --</option>
                            <option value="Subscription">Subscription</option>
                            <option value="License">License</option>
                            <option value="Software">Addon</option>
                        </select>
                        <InputError message={errors.software_type} />
                    </div>
                    <div>
                        <label className="form-label">
                            Penggunaan Aplikasi / Software{' '}
                            <span className="text-danger">*</span>
                        </label>
                        <select
                            className="form-field"
                            value={data.software_usage}
                            onChange={(e) =>
                                setData('software_usage', e.target.value)
                            }
                            required
                        >
                            <option value="">-- Pilih Penggunaan --</option>
                            <option value="individu">Individu</option>
                            <option value="team">Team</option>
                        </select>
                        <InputError message={errors.software_usage} />
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5 md:grid-cols-3">
                    <div>
                        <label className="form-label">
                            Jumlah Lisensi / User{' '}
                            <span className="text-danger">*</span>
                        </label>
                        <input
                            type="number"
                            min="1"
                            className="form-field"
                            value={data.license_count}
                            onChange={(e) =>
                                setData('license_count', e.target.value)
                            }
                            required
                        />
                        <InputError message={errors.license_count} />
                    </div>
                    <div>
                        <label className="form-label">
                            Durasi (Bulan) <span className="text-danger">*</span>
                        </label>
                        <input
                            type="number"
                            min="1"
                            placeholder="Contoh: 12 (untuk 12 bulan)"
                            className="form-field"
                            value={data.duration_months}
                            onChange={(e) =>
                                setData('duration_months', e.target.value)
                            }
                            disabled={data.software_type === 'License'}
                        />
                        <p className="mt-1.5 text-xs text-ink-faint">
                            Kosongkan kalau jenisnya License (gak butuh durasi).
                        </p>
                        <InputError message={errors.duration_months} />
                    </div>
                    <div>
                        <label className="form-label">
                            Total Biaya (Rp){' '}
                            <span className="text-danger">*</span>
                        </label>
                        <input
                            type="number"
                            placeholder="Contoh: 1000000"
                            className="form-field"
                            value={data.estimated_cost}
                            onChange={(e) =>
                                setData('estimated_cost', e.target.value)
                            }
                            required
                        />
                        <InputError message={errors.estimated_cost} />
                    </div>
                </div>

                <div>
                    <label className="form-label">
                        Alasan Pengajuan &amp; Jelaskan Spesifikasi Khusus (Jika
                        Ada) <span className="text-danger">*</span>
                    </label>
                    <textarea
                        className="form-field min-h-[88px] leading-relaxed"
                        rows="4"
                        placeholder="Masukan alasan pengajuan"
                        value={data.justification}
                        onChange={(e) => setData('justification', e.target.value)}
                        required
                    ></textarea>
                    <InputError message={errors.justification} />
                </div>

                <div className="flex items-start gap-3 rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5">
                    <input
                        id="digital-signature-software"
                        type="checkbox"
                        className="mt-0.5 h-4 w-4 shrink-0 rounded border-[1.5px] border-line bg-canvas text-accent focus:ring-4 focus:ring-accent-soft focus:ring-offset-0"
                        checked={data.digital_signature}
                        onChange={(e) =>
                            setData('digital_signature', e.target.checked)
                        }
                        required
                    />
                    <label
                        htmlFor="digital-signature-software"
                        className="cursor-pointer text-[12.5px] font-semibold leading-relaxed text-ink-muted"
                    >
                        Dengan mencentang ini, saya menyatakan bahwa informasi
                        yang saya berikan sudah benar!
                    </label>
                </div>
                <InputError message={errors.digital_signature} />

                <div className="flex justify-end border-t border-line pt-[18px]">
                    <PrimaryButton
                        type="submit"
                        disabled={!data.digital_signature || processing}
                    >
                        {processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    );
};

export default SoftwareForm;

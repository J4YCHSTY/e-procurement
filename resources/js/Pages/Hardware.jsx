import InputError from '@/Components/InputError';
import PreferenceImageField from '@/Components/PreferenceImageField';
import PrimaryButton from '@/Components/PrimaryButton';
import SignatureGate from '@/Components/SignatureGate';
import { useForm, usePage } from '@inertiajs/react';

const HardwareForm = () => {
    const user = usePage().props.auth.user;
    const today = new Date().toISOString().split('T')[0];
    const { data, setData, post, processing, errors } = useForm({
        request_date: today,
        hardware_type: '',
        hardware_recommendation: '',
        custom_hardware_name: '',
        preference_image: null,
        justification: '',
        digital_signature: false,
    });
    const recommendedDevices = {
        laptop: [
            { id: 'l1', name: 'Lenovo ThinkPad T14 (Standard Office)' },
            { id: 'l2', name: 'MacBook Pro 14 M3 (Design/Engineering)' },
            { id: 'l3', name: 'Dell Latitude 3420 (Entry Level)' },
        ],
        pc_desktop: [
            { id: 'p1', name: 'Dell OptiPlex 7000 (Standard Desk)' },
            { id: 'p2', name: 'Custom Build i9/RTX 4080 (Heavy Rendering)' },
        ],
        monitor: [
            { id: 'm1', name: 'Dell Ultrasharp 24 Inch' },
            { id: 'm2', name: 'LG 27 Inch 4K Monitor' },
        ],
    };
    const currentRecommendations = recommendedDevices[data.hardware_type] || [];

    // Kalau pemohon memilih perangkat di luar standar, tidak ada spesifikasi
    // baku yang bisa dijadikan acuan - jadi gambarnya yang jadi acuan.
    const preferenceRequired = data.hardware_recommendation === 'custom';

    const submitHardware = (e) => {
        e.preventDefault();
        post(route('request.hardware.store'));
    };

    return (
        <div className="mt-[22px] border-t border-line pt-[22px]">
            <h3 className="text-[15.5px] font-extrabold text-ink">
                Form Pengajuan Hardware
            </h3>
            <p className="mb-5 mt-0.5 text-[12.5px] text-ink-muted">
                Lengkapi detail perangkat yang kamu butuhkan.
            </p>

            <form className="space-y-4" onSubmit={submitHardware}>
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
                            Pilih Jenis Perangkat Yang Dibutuhkan{' '}
                            <span className="text-danger">*</span>
                        </label>
                        <select
                            className="form-field"
                            value={data.hardware_type}
                            onChange={(e) => {
                                setData('hardware_type', e.target.value);
                                setData('hardware_recommendation', '');
                            }}
                            required
                        >
                            <option value="">-- Pilih Jenis Perangkat --</option>
                            <option value="laptop">Laptop</option>
                            <option value="pc_desktop">Desktop</option>
                            <option value="peripherals">
                                Peripherals (Headphone, Mouse, Keyboard)
                            </option>
                            <option value="gadget">Gadget</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        <InputError message={errors.hardware_type} />
                    </div>
                </div>

                {currentRecommendations.length > 0 && (
                    <div className="rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5">
                        <label className="form-label">
                            Rekomendasi Perangkat Standar Perusahaan{' '}
                            <span className="text-danger">*</span>
                        </label>
                        <p className="mb-2.5 text-xs text-ink-muted">
                            Memilih spesifikasi dari standar perusahaan akan
                            mempercepat proses persetujuan pengajuan Anda.
                        </p>
                        <select
                            className="form-field"
                            value={data.hardware_recommendation}
                            onChange={(e) => {
                                setData(
                                    'hardware_recommendation',
                                    e.target.value,
                                );

                                if (e.target.value !== 'custom') {
                                    setData('custom_hardware_name', '');
                                }
                            }}
                            required
                        >
                            <option value="">
                                -- Pilih Spesifikasi Standar IT --
                            </option>
                            {currentRecommendations.map((device) => (
                                <option key={device.id} value={device.id}>
                                    {device.name}
                                </option>
                            ))}
                            <option value="custom">
                                Ajukan Perangkat Lain (Di luar standar)
                            </option>
                        </select>
                        <InputError message={errors.hardware_recommendation} />
                    </div>
                )}

                {preferenceRequired && (
                    <div>
                        <label className="form-label">
                            Nama Perangkat yang Diajukan{' '}
                            <span className="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            className="form-field"
                            placeholder="Contoh: MacBook Pro 14 M3 - 16GB / 512GB"
                            value={data.custom_hardware_name}
                            onChange={(e) =>
                                setData('custom_hardware_name', e.target.value)
                            }
                        />
                        <p className="mt-1.5 text-xs text-ink-faint">
                            Tulis merek dan tipenya selengkap mungkin, ini yang
                            dipakai procurement buat mencari penawaran.
                        </p>
                        <InputError message={errors.custom_hardware_name} />
                    </div>
                )}

                <PreferenceImageField
                    value={data.preference_image}
                    onChange={(file) => setData('preference_image', file)}
                    error={errors.preference_image}
                    required={preferenceRequired}
                    hint={
                        preferenceRequired
                            ? 'Karena perangkatnya di luar standar perusahaan, lampirkan tangkapan layar barang yang kamu maksud supaya procurement tahu persis yang dicari.'
                            : undefined
                    }
                />

                <div>
                    <label className="form-label">
                        Alasan Pengajuan &amp; Jelaskan Spesifikasi Khusus (Jika
                        Ada) <span className="text-danger">*</span>
                    </label>
                    <textarea
                        className="form-field min-h-[88px] leading-relaxed"
                        rows="4"
                        placeholder="Masukan alasan pengajuan dan spesifikasi khusus (jika diperlukan)"
                        value={data.justification}
                        onChange={(e) => setData('justification', e.target.value)}
                        required
                    ></textarea>
                    <InputError message={errors.justification} />
                </div>

                <SignatureGate />

                <div className="flex items-start gap-3 rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5">
                    <input
                        id="digital-signature"
                        type="checkbox"
                        className="mt-0.5 h-4 w-4 shrink-0 rounded border-[1.5px] border-line bg-canvas text-accent focus:ring-4 focus:ring-accent-soft focus:ring-offset-0"
                        checked={data.digital_signature}
                        onChange={(e) =>
                            setData('digital_signature', e.target.checked)
                        }
                    />
                    <label
                        htmlFor="digital-signature"
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
                        disabled={
                            !data.digital_signature ||
                            !user.has_signature ||
                            processing
                        }
                    >
                        {processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    );
};

export default HardwareForm;

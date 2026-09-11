import {
  IconAlertTriangle,
  IconApp,
  IconArrowLeft,
  IconCheckCheck,
  IconCheckCircle,
  IconClock,
  IconDevice,
  IconDotCircle,
  IconFile,
  IconImage,
  IconSignature,
  IconTruck,
  IconXCircle,
} from "@/Components/Icons";
import InputError from "@/Components/InputError";
import PrimaryButton from "@/Components/PrimaryButton";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";

// Disamain sama App\Enums\RequestStatus di backend. Satu-satunya tempat
// label status ditulis untuk halaman ini.
const STATUS_META = {
  WAITING_FOR_HEAD_APPROVAL: {
    label: "Menunggu Kepala Departemen",
    tone: "warn",
    icon: IconClock,
  },
  WAITING_FOR_HEAD_IT_APPROVAL: {
    label: "Menunggu Head of IT",
    tone: "warn",
    icon: IconClock,
  },
  ON_EXTERNAL_PROCESS: {
    label: "Diproses Eksternal",
    tone: "ok",
    icon: IconCheckCircle,
  },
  ITEM_ON_THE_WAY: {
    label: "Barang Dalam Perjalanan",
    tone: "ok",
    icon: IconTruck,
  },
  WAITING_FOR_BAST_SIGNATURE: {
    label: "Menunggu TTD BAST",
    tone: "warn",
    icon: IconSignature,
  },
  COMPLETED: { label: "Selesai", tone: "done", icon: IconCheckCheck },
  REJECTED: { label: "Ditolak", tone: "bad", icon: IconXCircle },
};

const TONE_CLASS = {
  warn: "bg-warning-soft text-warning",
  ok: "bg-success-soft text-success",
  done: "bg-accent-soft text-accent-deep",
  bad: "bg-danger-soft text-danger",
};

function StatusPill({ status, className = "" }) {
  const meta = STATUS_META[status] ?? {
    label: status,
    tone: "warn",
    icon: IconClock,
  };
  const Icon = meta.icon;

  return (
    <span
      className={`inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11.5px] font-bold ${TONE_CLASS[meta.tone]} ${className}`}
    >
      <Icon className="h-3 w-3" />
      {meta.label}
    </span>
  );
}

function Card({ title, description, children, action }) {
  return (
    <section className="overflow-hidden rounded-2xl border border-line bg-surface shadow-card">
      {title && (
        <header className="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4">
          <div>
            <h2 className="text-[14.5px] font-extrabold text-ink">{title}</h2>
            {description && (
              <p className="mt-0.5 text-[12.5px] text-ink-muted">
                {description}
              </p>
            )}
          </div>
          {action}
        </header>
      )}
      <div className="px-6 py-5">{children}</div>
    </section>
  );
}

function Field({ label, value }) {
  return (
    <div>
      <div className="text-[11px] font-bold uppercase tracking-wide text-ink-faint">
        {label}
      </div>
      <div className="mt-1 text-[13.5px] font-semibold text-ink">
        {value || "-"}
      </div>
    </div>
  );
}

/**
 * Waktu ditampilkan apa adanya dalam zona waktu pembaca. Kejadian di linimasa
 * adalah bukti alur, jadi jamnya ikut ditampilkan - bukan cuma tanggalnya.
 */
function formatMoment(iso) {
  if (!iso) {
    return "";
  }

  return new Date(iso).toLocaleString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function Timeline({ events }) {
  if (!events?.length) {
    return (
      <p className="text-[13px] text-ink-muted">
        Belum ada kejadian yang tercatat.
      </p>
    );
  }

  return (
    <ol className="flex flex-col">
      {events.map((event, index) => {
        const isLast = index === events.length - 1;
        const meta = STATUS_META[event.to_status];
        const Icon = meta?.icon ?? IconDotCircle;
        const isBad = event.to_status === "REJECTED";

        return (
          <li key={event.id} className="flex gap-3.5">
            <div className="flex flex-col items-center">
              <span
                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${
                  isBad
                    ? "bg-danger-soft text-danger"
                    : "bg-accent-soft text-accent-deep"
                }`}
              >
                <Icon className="h-[15px] w-[15px]" />
              </span>
              {/* Garis penyambung sengaja tidak digambar di
                                kejadian terakhir supaya tidak kelihatan
                                seperti masih ada lanjutannya. */}
              {!isLast && <span className="my-1 w-px flex-1 bg-line" />}
            </div>

            <div className={isLast ? "pb-0" : "pb-5"}>
              <div className="text-[13.5px] font-bold text-ink">
                {event.title}
              </div>
              <div className="mt-0.5 text-xs text-ink-muted">
                {event.actor_name ?? "Sistem"}
                {event.created_at && (
                  <> &middot; {formatMoment(event.created_at)}</>
                )}
              </div>
              {event.note && (
                <p className="mt-1.5 rounded-[10px] bg-surface-sunken px-3 py-2 text-xs italic text-ink-muted">
                  &ldquo;{event.note}&rdquo;
                </p>
              )}
            </div>
          </li>
        );
      })}
    </ol>
  );
}

/**
 * Kotak aksi IT Admin waktu barangnya sudah sampai. Nomor seri wajib -
 * tanpa itu BAST tidak bisa membuktikan unit mana yang diserahkan.
 */
function HandoverForm({ request }) {
  const { data, setData, post, processing, errors } = useForm({
    item_identifier: "",
    note: "",
  });
  const isSoftware = request.type_key === "software";

  const submit = (e) => {
    e.preventDefault();
    post(
      route("request.mark-handed-over", {
        type: request.type_key,
        id: request.id,
      }),
      { preserveScroll: true },
    );
  };

  return (
    <form onSubmit={submit} className="space-y-4">
      <div>
        <label className="form-label">
          {isSoftware ? "Kunci Lisensi" : "Nomor Seri Barang"}{" "}
          <span className="text-danger">*</span>
        </label>
        <input
          type="text"
          className="form-field"
          placeholder={
            isSoftware
              ? "Contoh: XXXXX-XXXXX-XXXXX-XXXXX"
              : "Contoh: C02ZK1ZTLVDL"
          }
          value={data.item_identifier}
          onChange={(e) => setData("item_identifier", e.target.value)}
        />
        <p className="mt-1.5 text-xs text-ink-faint">
          Ini yang tercetak di BAST sebagai penanda unit yang diserahkan.
        </p>
        <InputError message={errors.item_identifier} />
      </div>

      <div>
        <label className="form-label">Catatan (opsional)</label>
        <textarea
          className="form-field min-h-[70px] leading-relaxed"
          rows="2"
          placeholder="Contoh: diserahkan langsung di ruang IT, kelengkapan lengkap."
          value={data.note}
          onChange={(e) => setData("note", e.target.value)}
        />
        <InputError message={errors.note} />
      </div>

      <div className="flex justify-end">
        <PrimaryButton type="submit" disabled={processing}>
          {processing ? "Menyimpan..." : "Serahkan & Terbitkan BAST"}
        </PrimaryButton>
      </div>
    </form>
  );
}

function OnTheWayForm({ request }) {
  const { data, setData, post, processing, errors } = useForm({ note: "" });

  const submit = (e) => {
    e.preventDefault();
    post(
      route("request.mark-on-the-way", {
        type: request.type_key,
        id: request.id,
      }),
      { preserveScroll: true },
    );
  };

  return (
    <form onSubmit={submit} className="space-y-4">
      <div>
        <label className="form-label">Catatan (opsional)</label>
        <textarea
          className="form-field min-h-[70px] leading-relaxed"
          rows="2"
          placeholder="Contoh: PO sudah terbit, estimasi barang tiba minggu depan."
          value={data.note}
          onChange={(e) => setData("note", e.target.value)}
        />
        <p className="mt-1.5 text-xs text-ink-faint">
          Catatan ini terlihat oleh pemohon di linimasa, jadi dia tidak perlu
          bertanya ke IT soal posisi barangnya.
        </p>
        <InputError message={errors.note} />
      </div>

      <div className="flex justify-end">
        <PrimaryButton type="submit" disabled={processing}>
          {processing ? "Menyimpan..." : "Tandai Barang Dikirim"}
        </PrimaryButton>
      </div>
    </form>
  );
}

function SignBastPanel({ request, hasSignature }) {
  const [confirmed, setConfirmed] = useState(false);
  const { post, processing } = useForm({});

  const submit = () => {
    post(
      route("request.sign-bast", {
        type: request.type_key,
        id: request.id,
      }),
      { preserveScroll: true },
    );
  };

  if (!hasSignature) {
    return (
      <div className="flex items-start gap-2.5 rounded-[14px] border border-warning/40 bg-warning-soft px-4 py-3.5">
        <IconAlertTriangle className="mt-px h-[17px] w-[17px] shrink-0 text-warning" />
        <p className="text-[12.5px] font-semibold leading-relaxed text-warning">
          Kamu belum punya tanda tangan digital. Buat dulu lewat menu{" "}
          <Link
            href={route("profile.edit")}
            className="underline underline-offset-2"
          >
            Profil Saya
          </Link>
          , baru BAST ini bisa ditandatangani.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="rounded-[14px] border border-line bg-surface-sunken px-4 py-3.5">
        <div className="text-[13px] font-bold text-ink">
          Periksa dulu sebelum menandatangani
        </div>
        <ul className="mt-2 flex flex-col gap-1.5 text-[12.5px] text-ink-muted">
          <li>
            Barang yang diterima:{" "}
            <strong className="text-ink">{request.title}</strong>
          </li>
          <li>
            {request.type_key === "software" ? "Kunci lisensi" : "Nomor seri"}:{" "}
            <strong className="font-mono text-ink">
              {request.item_identifier || "-"}
            </strong>
          </li>
          <li>
            Diserahkan oleh:{" "}
            <strong className="text-ink">
              {request.handed_over_by_name ?? "-"}
            </strong>{" "}
            &middot; {formatMoment(request.handed_over_at)}
          </li>
        </ul>
      </div>

      <label className="flex cursor-pointer items-start gap-3 rounded-[14px] border border-line bg-surface px-4 py-3.5">
        <input
          type="checkbox"
          className="mt-0.5 h-4 w-4 shrink-0 rounded border-[1.5px] border-line bg-canvas text-accent focus:ring-4 focus:ring-accent-soft focus:ring-offset-0"
          checked={confirmed}
          onChange={(e) => setConfirmed(e.target.checked)}
        />
        <span className="text-[12.5px] font-semibold leading-relaxed text-ink-muted">
          Saya menyatakan barang di atas sudah saya terima dalam keadaan sesuai,
          dan tanda tangan digital saya boleh dilampirkan pada BAST ini.
        </span>
      </label>

      <div className="flex justify-end">
        <PrimaryButton onClick={submit} disabled={!confirmed || processing}>
          {processing ? "Menandatangani..." : "Tanda Tangani BAST"}
        </PrimaryButton>
      </div>
    </div>
  );
}

export default function RequestShow() {
  const { request, timeline, abilities, flash } = usePage().props;
  const TypeIcon = request.type === "Hardware" ? IconDevice : IconApp;

  return (
    <AuthenticatedLayout
      title="Detail Pengajuan"
      subtitle={`${request.type} · ${request.title}`}
    >
      <Head title={`Detail Pengajuan - ${request.title}`} />

      <div className="flex flex-col gap-5">
        {flash?.success && (
          <div className="flex items-start gap-2.5 rounded-2xl bg-success-soft px-4 py-3.5 text-[13px] font-semibold leading-relaxed text-success">
            <IconCheckCircle className="mt-px h-[17px] w-[17px] shrink-0" />
            {flash.success}
          </div>
        )}

        {flash?.error && (
          <div className="flex items-start gap-2.5 rounded-2xl bg-danger-soft px-4 py-3.5 text-[13px] font-semibold leading-relaxed text-danger">
            <IconXCircle className="mt-px h-[17px] w-[17px] shrink-0" />
            {flash.error}
          </div>
        )}

        <Link
          href={route("dashboard")}
          className="inline-flex w-fit items-center gap-1.5 text-[13px] font-bold text-accent-deep hover:underline"
        >
          <IconArrowLeft className="h-4 w-4" />
          Kembali ke Dashboard
        </Link>

        {/* --------------------------------- Kepala -------------------------------- */}
        <section className="rounded-2xl border border-line bg-surface px-6 py-5 shadow-card">
          <div className="flex flex-wrap items-start gap-4">
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-[14px] bg-accent-soft text-accent-deep">
              <TypeIcon className="h-5 w-5" />
            </span>

            <div className="min-w-0 flex-1">
              <div className="text-[17px] font-extrabold text-ink">
                {request.title}
              </div>
              <p className="mt-1 text-[12.5px] text-ink-muted">
                Diajukan oleh{" "}
                <strong className="text-ink">
                  {request.requester_name ?? "-"}
                </strong>
                {request.requester_department && (
                  <> &middot; {request.requester_department}</>
                )}{" "}
                &middot; {request.request_date}
              </p>
            </div>

            <StatusPill status={request.status} />
          </div>

          {request.status === "REJECTED" && request.rejected_by_name && (
            <p className="mt-4 rounded-[12px] bg-danger-soft px-4 py-3 text-[12.5px] font-semibold text-danger">
              Ditolak oleh {request.rejected_by_name} pada{" "}
              {formatMoment(request.rejected_at)}.
            </p>
          )}
        </section>

        <div className="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
          <div className="flex flex-col gap-5">
            {/* ------------------------------ Ringkasan ------------------------------ */}
            <Card title="Ringkasan Pengajuan">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field
                  label="Tanggal Permintaan"
                  value={request.request_date}
                />
                {request.fields.map((field) => (
                  <Field
                    key={field.label}
                    label={field.label}
                    value={field.value}
                  />
                ))}
                <Field label="Perusahaan" value={request.requester_entity} />
                <Field
                  label="Jabatan Pemohon"
                  value={request.requester_position}
                />
              </div>

              <div className="mt-5 border-t border-line pt-4">
                <div className="text-[11px] font-bold uppercase tracking-wide text-ink-faint">
                  Justifikasi
                </div>
                <p className="mt-1.5 text-[13px] italic leading-relaxed text-ink-muted">
                  &ldquo;{request.justification}&rdquo;
                </p>
              </div>

              {request.item_identifier && (
                <div className="mt-5 border-t border-line pt-4">
                  <Field
                    label={
                      request.type_key === "software"
                        ? "Kunci Lisensi"
                        : "Nomor Seri Barang"
                    }
                    value={request.item_identifier}
                  />
                </div>
              )}
            </Card>

            {/* --------------------------- Preferensi barang -------------------------- */}
            {request.preference_image_url && (
              <Card
                title="Preferensi Barang"
                description="Tangkapan layar yang diunggah pemohon saat membuat pengajuan."
              >
                <a
                  href={request.preference_image_url}
                  target="_blank"
                  rel="noreferrer"
                  className="block overflow-hidden rounded-[14px] border border-line bg-surface-sunken"
                >
                  <img
                    src={request.preference_image_url}
                    alt="Preferensi barang dari pemohon"
                    className="max-h-[380px] w-full object-contain"
                  />
                </a>
                <p className="mt-2 flex items-center gap-1.5 text-xs text-ink-faint">
                  <IconImage className="h-3.5 w-3.5" />
                  Klik untuk membuka ukuran penuh.
                </p>
              </Card>
            )}

            {/* ------------------------------- Dokumen ------------------------------- */}
            <Card
              title="Dokumen"
              description="Form Request & BAST yang menempel di pengajuan ini."
            >
              <div className="flex items-start gap-3 rounded-[14px] border border-dashed border-line bg-surface-sunken px-4 py-4">
                <IconFile className="mt-px h-[18px] w-[18px] shrink-0 text-ink-faint" />
                <div>
                  <div className="text-[13px] font-bold text-ink">
                    Belum ada dokumen tercetak
                  </div>
                  <p className="mt-1 text-[12.5px] leading-relaxed text-ink-muted">
                    Generator PDF-nya sedang dikerjakan. Semua bahan yang
                    dibutuhkannya - siapa menyetujui kapan, nomor seri, dan
                    tanda tangan tiap pihak - sudah tersimpan di linimasa
                    sebelah.
                  </p>
                </div>
              </div>
            </Card>
          </div>

          <div className="flex flex-col gap-5">
            {/* -------------------------- Aksi sesuai peran ------------------------- */}
            {abilities.markOnTheWay && (
              <Card
                title="Barang sudah dikirim?"
                description="PO sudah terbit dan barangnya dalam perjalanan."
              >
                <OnTheWayForm request={request} />
              </Card>
            )}

            {abilities.markHandedOver && (
              <Card
                title="Barang sudah diserahkan?"
                description="Catat nomor serinya, lalu BAST terbit dan menunggu tanda tangan pemohon."
              >
                <HandoverForm request={request} />
              </Card>
            )}

            {abilities.signBast && (
              <Card
                title="Tanda tangani BAST"
                description="Serah terimanya sah begitu kamu tanda tangan, dan pengajuan ini langsung selesai."
              >
                <SignBastPanel
                  request={request}
                  hasSignature={abilities.hasSignature}
                />
              </Card>
            )}

            {request.status === "WAITING_FOR_BAST_SIGNATURE" &&
              !abilities.signBast && (
                <Card title="Menunggu tanda tangan pemohon">
                  <p className="text-[13px] leading-relaxed text-ink-muted">
                    BAST sudah terbit dan menunggu{" "}
                    <strong className="text-ink">
                      {request.requester_name}
                    </strong>{" "}
                    menandatanganinya. Cuma penerima barang yang boleh
                    menandatangani - itulah gunanya BAST ada.
                  </p>
                </Card>
              )}

            {/* ------------------------------- Linimasa ------------------------------ */}
            <Card
              title="Linimasa"
              description="Setiap perubahan status tercatat otomatis."
            >
              <Timeline events={timeline} />
            </Card>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}

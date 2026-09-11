<?php

namespace App\Models\Concerns;

use App\Models\RequestEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Perilaku linimasa yang dipakai bareng HardwareRequest & SoftwareRequest.
 *
 * Ditaruh di trait, bukan di kelas induk bersama, karena keduanya memang dua
 * tabel yang berbeda isinya - yang sama cuma cara mereka berjalan melewati
 * tahapan. Trait menyalin perilakunya tanpa memaksa keduanya jadi satu bentuk.
 */
trait HasRequestTimeline
{
    /**
     * Tautan ke halaman detail, ikut terkirim ke frontend lewat $appends.
     *
     * Dibentuk di server supaya frontend tidak perlu tahu pola URL-nya dan
     * tidak bisa salah menyusunnya sendiri.
     */
    protected function detailUrl(): Attribute
    {
        return Attribute::get(fn () => route('request.show', [
            'type' => $this->requestTypeKey(),
            'id' => $this->id,
        ]));
    }

    public function events(): MorphMany
    {
        return $this->morphMany(RequestEvent::class, 'requestable')->oldest();
    }

    /**
     * IT Admin yang menyerahkan barangnya - dicetak di BAST sebagai pihak
     * yang menyerahkan.
     */
    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by_id');
    }

    /**
     * Catat satu perubahan status.
     *
     * Status ASAL wajib disebut pemanggilnya, sengaja tidak ditebak sendiri
     * dari model. Menebaknya pernah dicoba - diambil dari getOriginal() - dan
     * hasilnya salah untuk kejadian pertama: pengajuan yang baru dibuat memang
     * tidak berpindah dari mana-mana, tapi getOriginal() tetap mengembalikan
     * status awalnya, sehingga linimasa berbunyi seolah ada tahap sebelumnya.
     *
     * $fromStatus null artinya "ini kejadian pertama", bukan "tolong tebakkan".
     */
    public function recordEvent(string $toStatus, ?User $actor, ?string $fromStatus, ?string $note = null): RequestEvent
    {
        return $this->events()->create([
            'actor_id' => $actor?->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
        ]);
    }
}

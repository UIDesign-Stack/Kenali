<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConsultationStatus;
use App\Events\ConsultationStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReviewController;
use App\Models\Consultation;
use App\Models\ConsultationReview;
use App\Models\PsychologistProfile;
use App\Notifications\ConsultationStatusUpdated;
use App\Notifications\PsychologistVerified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PsychologistManagementController extends Controller
{
    public function index()
    {
        $psychologists = PsychologistProfile::with('user:id,name,email,is_active')
            ->withCount('consultations')
            ->withCount(['consultations as active_consultations_count' => function ($q) {
                $q->whereIn('status', ConsultationStatus::activeStatusValues());
            }])
            ->orderByDesc('is_verified')
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Admin/Psychologists/Index', [
            'psychologists' => $psychologists,
        ]);
    }

    public function toggleVerified(Request $request, PsychologistProfile $psychologistProfile)
    {
        $psychologistProfile->load('user:id,name');
        $name = $psychologistProfile->user?->name ?? 'Psikolog';

        $isVerified = $this->toggleFlag($psychologistProfile, 'is_verified');

        if ($isVerified === null) {
            return back()->withErrors([
                'psychologist' => "Verifikasi {$name} tidak bisa dibatalkan karena masih memiliki konsultasi yang sedang berjalan. Lihat & selesaikan/batalkan konsultasinya dulu.",
            ]);
        }

        Log::info('Status verifikasi psikolog diubah', [
            'psychologist_profile_id' => $psychologistProfile->id,
            'admin_id'                => $request->user()->id,
            'is_verified'             => $isVerified,
        ]);

        if ($isVerified) {
            rescue(fn () => $psychologistProfile->user?->notify(new PsychologistVerified()));
        }

        return back()->with('success', $isVerified
            ? "Psikolog {$name} berhasil diverifikasi."
            : "Verifikasi psikolog {$name} dibatalkan.");
    }

    public function toggleAvailable(Request $request, PsychologistProfile $psychologistProfile)
    {
        $psychologistProfile->load('user:id,name');
        $name = $psychologistProfile->user?->name ?? 'Psikolog';

        $isAvailable = $this->toggleFlag($psychologistProfile, 'is_available');

        if ($isAvailable === null) {
            return back()->withErrors([
                'psychologist' => "Psikolog {$name} tidak bisa dinonaktifkan karena masih memiliki konsultasi yang sedang berjalan. Lihat & selesaikan/batalkan konsultasinya dulu.",
            ]);
        }

        Log::info('Ketersediaan psikolog diubah', [
            'psychologist_profile_id' => $psychologistProfile->id,
            'admin_id'                => $request->user()->id,
            'is_available'            => $isAvailable,
        ]);

        return back()->with('success', $isAvailable
            ? "Psikolog {$name} kini tersedia untuk konsultasi baru."
            : "Psikolog {$name} dinonaktifkan sementara dari konsultasi baru.");
    }

    public function consultations(PsychologistProfile $psychologistProfile)
    {
        $psychologistProfile->load('user:id,name');

        $consultations = $psychologistProfile->consultations()
            ->select([
                'id', 'user_id', 'psychologist_profile_id', 'type', 'status',
                'scheduled_at', 'duration_minutes', 'cancelled_reason',
                'created_at', 'updated_at',
            ])
            ->with('user:id,name')
            ->latest()
            ->get();

        return Inertia::render('Admin/Psychologists/Consultations', [
            'psychologistProfile' => $psychologistProfile,
            'consultations'       => $consultations,
        ]);
    }

    public function forceCancelConsultation(Request $request, Consultation $consultation)
    {
        $validated = $request->validate([
            'cancelled_reason' => ['required', 'string', 'max:1000'],
        ]);

        $cancelled = DB::transaction(function () use ($consultation, $validated) {

            $locked = Consultation::whereKey($consultation->id)->lockForUpdate()->firstOrFail();

            $currentStatus = $locked->status instanceof ConsultationStatus
                ? $locked->status->value
                : $locked->status;

            if (in_array($currentStatus, ConsultationStatus::terminalStatusValues(), true)) {
                return false;
            }

            $locked->forceFill([
                'status'           => ConsultationStatus::Cancelled->value,
                'cancelled_reason' => '[Dibatalkan oleh admin] '.$validated['cancelled_reason'],
            ])->save();

            return true;
        });

        if (! $cancelled) {
            return back()->withErrors([
                'consultation' => 'Konsultasi ini sudah berstatus akhir, tidak bisa dibatalkan lagi.',
            ]);
        }
        Log::info('Konsultasi dibatalkan paksa oleh admin', [
            'consultation_id' => $consultation->id,
            'admin_id'        => $request->user()->id,
        ]);

        $consultation->refresh()->loadMissing('user', 'psychologistProfile.user');

        rescue(fn () => $consultation->user?->notify(new ConsultationStatusUpdated($consultation, 'force_cancelled')));
        rescue(fn () => $consultation->psychologistProfile?->user?->notify(new ConsultationStatusUpdated($consultation, 'force_cancelled')));
        rescue(fn () => broadcast(new ConsultationStatusChanged($consultation)));

        return back()->with('success', 'Konsultasi berhasil dibatalkan oleh admin.');
    }

    public function reviews(PsychologistProfile $psychologistProfile)
    {
        $psychologistProfile->load('user:id,name');

        $reviews = $psychologistProfile->reviews()
            ->with('user:id,name')
            ->latest()
            ->get();

        return Inertia::render('Admin/Psychologists/Reviews', [
            'psychologistProfile' => $psychologistProfile->only('id', 'rating_avg', 'rating_count') + [
                'user' => $psychologistProfile->user,
            ],
            'reviews' => $reviews,
        ]);
    }

    public function toggleReviewHidden(Request $request, ConsultationReview $review)
    {
        $isHidden = DB::transaction(function () use ($review) {
            $locked = ConsultationReview::whereKey($review->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill(['is_hidden' => ! $locked->is_hidden])->save();

            ReviewController::recalculate($locked->psychologist_profile_id);

            return (bool) $locked->is_hidden;
        });

        Log::info('Ulasan dimoderasi', [
            'review_id' => $review->id,
            'admin_id'  => $request->user()->id,
            'is_hidden' => $isHidden,
        ]);

        return back()->with('success', 'Status ulasan diperbarui.');
    }

    public function toggleReplyHidden(Request $request, ConsultationReview $review)
    {
        $isHidden = DB::transaction(function () use ($review) {
            $locked = ConsultationReview::whereKey($review->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill(['reply_hidden' => ! $locked->reply_hidden])->save();

            return (bool) $locked->reply_hidden;
        });

        Log::info('Balasan ulasan dimoderasi', [
            'review_id'    => $review->id,
            'admin_id'     => $request->user()->id,
            'reply_hidden' => $isHidden,
        ]);

        return back()->with('success', 'Status balasan diperbarui.');
    }

    /**
     * Balik nilai flag boolean pada profil psikolog ($flag hanya boleh diisi konstanta
     * dari dalam class ini, bukan input user).
     *
     * Baris profil dikunci sebelum cek "ada konsultasi berjalan", jadi cek dan update
     * tidak bisa terselip konsultasi baru di antaranya (lihat catatan soal
     * ConsultationController::store di ringkasan review).
     *
     * @return bool|null  nilai baru flag, atau null kalau ditolak karena masih ada konsultasi berjalan
     */
    protected function toggleFlag(PsychologistProfile $profile, string $flag): ?bool
    {
        return DB::transaction(function () use ($profile, $flag) {
            $locked = PsychologistProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();

            $turningOff = (bool) $locked->{$flag};

            if ($turningOff && $this->hasActiveConsultation($locked)) {
                return null;
            }

            $locked->forceFill([$flag => ! $turningOff])->save();

            return ! $turningOff;
        });
    }

    protected function hasActiveConsultation(PsychologistProfile $profile): bool
    {
        return $profile->consultations()
            ->whereIn('status', ConsultationStatus::activeStatusValues())
            ->exists();
    }
}

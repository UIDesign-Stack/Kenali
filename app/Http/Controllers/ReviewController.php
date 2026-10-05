<?php

namespace App\Http\Controllers;

use App\Enums\ConsultationStatus;
use App\Http\Requests\ReplyReviewRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Consultation;
use App\Models\ConsultationReview;
use App\Models\PsychologistProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReviewController extends Controller
{

    public function index(PsychologistProfile $psychologistProfile)
    {
        $reviews = $psychologistProfile->reviews()
            ->visible()
            ->latest()
            ->get(['id', 'rating', 'comment', 'reply', 'replied_at', 'reply_hidden', 'created_at'])
            ->map(fn ($r) => [
                'id'         => $r->id,
                'rating'     => $r->rating,
                'comment'    => $r->comment,
                'reply'      => $r->reply_hidden ? null : $r->reply,
                'created_at' => $r->created_at,
            ]);

        return \Inertia\Inertia::render('Psychologist/Reviews', [
            'psychologist' => [
                'id'             => $psychologistProfile->id,
                'name'           => $psychologistProfile->user->name,
                'specialization' => $psychologistProfile->specialization,
                'rating_avg'     => $psychologistProfile->rating_avg,
                'rating_count'   => $psychologistProfile->rating_count,
            ],
            'reviews' => $reviews,
        ]);
    }
    /** Pasien mengirim ulasan untuk konsultasi yang sudah selesai. */
    public function store(StoreReviewRequest $request, Consultation $consultation): RedirectResponse
    {
        abort_if($consultation->user_id !== $request->user()->id, 403);

        $error = DB::transaction(function () use ($request, $consultation) {
            $locked = Consultation::whereKey($consultation->id)->lockForUpdate()->first();

            if ($locked->status !== ConsultationStatus::Completed->value) {
                return 'Ulasan hanya bisa diberikan setelah konsultasi selesai.';
            }

            if ($locked->review()->exists()) {
                return 'Konsultasi ini sudah pernah kamu ulas.';
            }

            ConsultationReview::create([
                'consultation_id'         => $locked->id,
                'psychologist_profile_id' => $locked->psychologist_profile_id,
                'user_id'                 => $request->user()->id,
                'rating'                  => $request->validated('rating'),
                'comment'                 => $request->validated('comment'),
            ]);

            $this->recalculate($locked->psychologist_profile_id);

            return null;
        });

        if ($error) {
            return back()->withErrors(['review' => $error]);
        }

        return back()->with('success', 'Terima kasih, ulasanmu terkirim.');
    }

    /** Pasien mengubah ulasannya dalam 24 jam. */
    public function update(StoreReviewRequest $request, ConsultationReview $review): RedirectResponse
    {
        abort_if($review->user_id !== $request->user()->id, 403);

        $error = DB::transaction(function () use ($request, $review) {
            $locked = ConsultationReview::whereKey($review->id)->lockForUpdate()->first();

            if (! $locked->isEditableByAuthor()) {
                return 'Ulasan hanya bisa diubah dalam 24 jam setelah dikirim.';
            }

            $locked->update([
                'rating'  => $request->validated('rating'),
                'comment' => $request->validated('comment'),
            ]);

            $this->recalculate($locked->psychologist_profile_id);

            return null;
        });

        if ($error) {
            return back()->withErrors(['review' => $error]);
        }

        return back()->with('success', 'Ulasan diperbarui.');
    }

    /** Psikolog terkait membalas ulasan (satu balasan, boleh diubah 24 jam). */
    public function reply(ReplyReviewRequest $request, ConsultationReview $review): RedirectResponse
    {
        $review->loadMissing('psychologistProfile');
        abort_if($review->psychologistProfile->user_id !== $request->user()->id, 403);

        $error = DB::transaction(function () use ($request, $review) {
            $locked = ConsultationReview::whereKey($review->id)->lockForUpdate()->first();

            if ($locked->is_hidden) {
                return 'Ulasan ini tidak dapat dibalas.';
            }

            if (! $locked->isReplyEditable()) {
                return 'Balasan hanya bisa diubah dalam 24 jam setelah dikirim.';
            }

            $locked->update([
                'reply'        => $request->validated('reply'),
                'replied_at'   => $locked->replied_at ?? now(),
                'reply_hidden' => false,
            ]);

            return null;
        });

        if ($error) {
            return back()->withErrors(['review' => $error]);
        }

        return back()->with('success', 'Balasan tersimpan.');
    }

    /** Hitung ulang ringkasan rating (hanya ulasan yang tidak disembunyikan). */
    public static function recalculate(int $profileId): void
    {
        PsychologistProfile::whereKey($profileId)->lockForUpdate()->first();

        $stats = ConsultationReview::where('psychologist_profile_id', $profileId)
            ->where('is_hidden', false)
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating), 0) as a')
            ->first();

        DB::table('psychologist_profiles')->where('id', $profileId)->update([
            'rating_count' => (int) $stats->c,
            'rating_avg'   => round((float) $stats->a, 2),
        ]);
    }
}
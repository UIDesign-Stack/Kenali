<?php

namespace App\Http\Controllers;

use App\Actions\MarkConsultationMessagesAsRead;
use App\Enums\ConsultationStatus;
use App\Events\ConsultationMessageSent;
use App\Events\ConsultationStatusChanged;
use App\Http\Requests\SendPsychologistMessageRequest;
use App\Http\Requests\UpdateConsultationStatusRequest;
use App\Models\Consultation;
use App\Notifications\ConsultationStatusUpdated;
use App\Notifications\NewConsultationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PsychologistConsultationController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->psychologistProfile;
        abort_unless($profile, 403, 'Akun ini belum punya profil psikolog.');

        $consultations = $profile->consultations()
            ->with('user:id,name')
            ->latest()
            ->get();

        return Inertia::render('Psychologist/Consultations/Index', [
            'consultations' => $consultations,
            'profile'       => $profile->only('id', 'rating_avg', 'rating_count'),
        ]);
    }

    public function show(Consultation $consultation, Request $request, MarkConsultationMessagesAsRead $markAsRead)
    {
        $this->authorizeOwnership($consultation, $request);

        if ($consultation->status === ConsultationStatus::Scheduled->value) {
            $markAsRead->handle($consultation, $request->user());
        }

        $consultation->load([
            'user:id,name,life_phase',
            'testSession.result.details' => fn ($q) => $q->orderBy('rank')->with('alternative:id,name'),
            'messages.sender:id,name',
            'review:id,consultation_id,rating,comment,is_hidden,reply,replied_at,reply_hidden,created_at',
        ]);

        $review = $consultation->review;

        return Inertia::render('Psychologist/Consultations/Show', [
            'consultation'  => $consultation,
            'replyEditable' => $review && ! $review->is_hidden ? $review->isReplyEditable() : false,
            'closesAt'      => $consultation->autoCloseAt()?->toIso8601String(),
        ]);
    }

    public function updateStatus(UpdateConsultationStatusRequest $request, Consultation $consultation)
    {
        $validated = $request->validated();

        $context = DB::transaction(function () use ($validated, $consultation) {
            $locked = Consultation::whereKey($consultation->id)->lockForUpdate()->first();

            abort_if(
                in_array($locked->status, ConsultationStatus::terminalStatusValues()),
                422,
                'Konsultasi ini sudah berstatus akhir dan tidak bisa diubah lagi.'
            );

            abort_if(
                $validated['status'] === ConsultationStatus::Completed->value
                    && $locked->status !== ConsultationStatus::Scheduled->value,
                422,
                'Konsultasi harus dijadwalkan (scheduled) dulu sebelum bisa ditandai selesai.'
            );

            abort_if(
                $validated['status'] === ConsultationStatus::Scheduled->value
                    && $locked->status !== ConsultationStatus::Pending->value,
                422,
                'Hanya permintaan yang masih menunggu yang bisa dijadwalkan.'
            );

            $previousStatus = $locked->status;

            // Simpan hanya field yang relevan untuk status tujuan
            $payload = ['status' => $validated['status']];

            if ($validated['status'] === ConsultationStatus::Scheduled->value) {
                $payload['scheduled_at'] = $validated['scheduled_at'];
                if (array_key_exists('notes', $validated)) {
                    $payload['notes'] = $validated['notes'];
                }
            }

            if ($validated['status'] === ConsultationStatus::Cancelled->value) {
                $payload['cancelled_reason'] = $validated['cancelled_reason'];
            }

            $locked->update($payload);

            return match (true) {
                $validated['status'] === ConsultationStatus::Scheduled->value => 'scheduled',
                $previousStatus === ConsultationStatus::Pending->value
                    && $validated['status'] === ConsultationStatus::Cancelled->value => 'rejected',
                $previousStatus === ConsultationStatus::Scheduled->value
                    && $validated['status'] === ConsultationStatus::Cancelled->value => 'cancelled_after_scheduled',
                $validated['status'] === ConsultationStatus::Completed->value => 'completed',
                default => null,
            };
        });

        $consultation->refresh();

        if ($context) {
            $consultation->loadMissing('user');
            $consultation->user->notify(new ConsultationStatusUpdated($consultation, $context));
        }

        broadcast(new ConsultationStatusChanged($consultation));

        return back()->with('success', 'Status konsultasi diperbarui.');
    }

    public function sendMessage(SendPsychologistMessageRequest $request, Consultation $consultation)
    {
        $this->authorizeOwnership($consultation, $request);

        abort_if(
            $consultation->status !== ConsultationStatus::Scheduled->value,
            422,
            'Konsultasi ini belum/tidak bisa menerima pesan (status: '.$consultation->status.').'
        );

        $message = $consultation->messages()->create([
            'sender_id' => $request->user()->id,
            'message'   => $request->validated('message'),
            'sent_at'   => now(),
        ]);

        $message->load('sender:id,name');

        // notify DULU, baru broadcast, supaya notifikasi sudah ada di DB saat penerima memanggil /read
        $consultation->loadMissing('user');
        $consultation->user->notify(new NewConsultationMessage($message));

        broadcast(new ConsultationMessageSent($message))->toOthers();

        return response()->json(['data' => $message]);
    }

    public function markAsRead(Consultation $consultation, Request $request, MarkConsultationMessagesAsRead $markAsRead)
    {
        $this->authorizeOwnership($consultation, $request);
        abort_unless($consultation->status === ConsultationStatus::Scheduled->value, 422);

        $markAsRead->handle($consultation, $request->user());

        return response()->json(['ok' => true]);
    }

    private function authorizeOwnership(Consultation $consultation, Request $request): void
    {
        abort_if($consultation->psychologistProfile?->user_id !== $request->user()->id, 403);
    }
}

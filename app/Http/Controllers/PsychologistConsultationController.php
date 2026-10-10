<?php

namespace App\Http\Controllers;

use App\Actions\MarkConsultationMessagesAsRead;
use App\Enums\ConsultationStatus;
use App\Events\ConsultationMessageSent;
use App\Events\ConsultationStatusChanged;
use App\Http\Requests\SendPsychologistMessageRequest;
use App\Http\Requests\UpdateConsultationStatusRequest;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Notifications\ConsultationStatusUpdated;
use App\Notifications\NewConsultationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PsychologistConsultationController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->psychologistProfile;
        abort_unless($profile, 403, 'Akun ini belum punya profil psikolog.');

        $consultations = $profile->consultations()
            ->select(['id', 'user_id', 'psychologist_profile_id', 'type', 'status', 'scheduled_at', 'created_at'])
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
            'messages.sender:id,name',
            'review:id,consultation_id,rating,comment,is_hidden,reply,replied_at,reply_hidden,created_at',
        ]);

        $review = $consultation->review;

        $topAlternative = $consultation->status === ConsultationStatus::Cancelled->value
            ? null
            : $consultation->testSession?->result?->details()
                ->orderBy('rank')
                ->with('alternative:id,name')
                ->first()?->alternative?->name;

        return Inertia::render('Psychologist/Consultations/Show', [
            'consultation'   => $consultation,
            'topAlternative' => $topAlternative,
            'replyEditable'  => $review && ! $review->is_hidden ? $review->isReplyEditable() : false,
            'closesAt'       => $consultation->autoCloseAt()?->toIso8601String(),
        ]);
    }

    public function updateStatus(UpdateConsultationStatusRequest $request, Consultation $consultation)
    {

        $this->authorizeOwnership($consultation, $request);

        $validated = $request->validated();

        abort_unless(
            in_array($validated['status'], [
                ConsultationStatus::Scheduled->value,
                ConsultationStatus::Completed->value,
                ConsultationStatus::Cancelled->value,
            ], true),
            422,
            'Status tujuan tidak valid.'
        );

        [$context, $previousStatus] = DB::transaction(function () use ($validated, $consultation) {
            $locked = Consultation::whereKey($consultation->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ConsultationStatus::terminalStatusValues())) {
                throw ValidationException::withMessages([
                    'status' => 'Konsultasi ini sudah berstatus akhir dan tidak bisa diubah lagi.',
                ]);
            }

            if ($validated['status'] === ConsultationStatus::Completed->value
                && $locked->status !== ConsultationStatus::Scheduled->value) {
                throw ValidationException::withMessages([
                    'status' => 'Konsultasi harus dijadwalkan dulu sebelum bisa ditandai selesai.',
                ]);
            }

            if ($validated['status'] === ConsultationStatus::Scheduled->value
                && $locked->status !== ConsultationStatus::Pending->value) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya permintaan yang masih menunggu yang bisa dijadwalkan.',
                ]);
            }

            $previousStatus = $locked->status;

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

            $locked->forceFill($payload)->save();

            $context = match (true) {
                $validated['status'] === ConsultationStatus::Scheduled->value => 'scheduled',
                $previousStatus === ConsultationStatus::Pending->value
                    && $validated['status'] === ConsultationStatus::Cancelled->value => 'rejected',
                $previousStatus === ConsultationStatus::Scheduled->value
                    && $validated['status'] === ConsultationStatus::Cancelled->value => 'cancelled_after_scheduled',
                $validated['status'] === ConsultationStatus::Completed->value => 'completed',
                default => null,
            };

            return [$context, $previousStatus];
        });

        Log::info('Status konsultasi diubah oleh psikolog', [
            'consultation_id' => $consultation->id,
            'psychologist_id' => $request->user()->id,
            'from'            => $previousStatus,
            'to'              => $validated['status'],
        ]);

        $consultation->refresh();

        if ($context) {
            rescue(function () use ($consultation, $context) {
                $consultation->loadMissing('user');
                $consultation->user?->notify(new ConsultationStatusUpdated($consultation, $context));
            });
        }

        rescue(fn () => broadcast(new ConsultationStatusChanged($consultation)));

        return back()->with('success', 'Status konsultasi diperbarui.');
    }

    public function sendMessage(SendPsychologistMessageRequest $request, Consultation $consultation)
    {
        $this->authorizeOwnership($consultation, $request);

        $message = DB::transaction(function () use ($consultation, $request) {

            $locked = Consultation::whereKey($consultation->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ConsultationStatus::Scheduled->value) {
                return null;
            }

            $message = new ConsultationMessage();
            $message->consultation_id = $locked->id;
            $message->sender_id       = $request->user()->id;
            $message->message         = $request->validated('message');
            $message->sent_at         = now();
            $message->save();

            return $message;
        });

        abort_if(
            $message === null,
            422,
            'Konsultasi ini belum/tidak bisa menerima pesan (status: '.$consultation->status.').'
        );

        $message->load('sender:id,name');

        rescue(function () use ($consultation, $message) {
            $consultation->loadMissing('user');
            $consultation->user?->notify(new NewConsultationMessage($message));
        });

        rescue(fn () => broadcast(new ConsultationMessageSent($message))->toOthers());

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
        $profileId = $request->user()->psychologistProfile?->id;

        abort_if(
            $profileId === null || (int) $consultation->psychologist_profile_id !== (int) $profileId,
            404
        );
    }
}

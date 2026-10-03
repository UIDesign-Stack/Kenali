<?php

namespace App\Http\Controllers;

use App\Enums\ConsultationStatus;
use App\Enums\LifePhase;
use App\Enums\TestSessionStatus;
use App\Events\ConsultationMessageSent;
use App\Http\Requests\SendConsultationMessageRequest;
use App\Http\Requests\StoreConsultationRequest;
use App\Models\Consultation;
use App\Models\PsychologistProfile;
use App\Notifications\ConsultationStatusUpdated;
use App\Notifications\NewConsultationMessage;
use App\Actions\MarkConsultationMessagesAsRead;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $consultations = $request->user()
            ->consultations()
            ->with('psychologistProfile.user:id,name')
            ->latest()
            ->get();

        return Inertia::render('Consultations/Index', [
            'consultations' => $consultations,
        ]);
    }

    public function create(Request $request)
    {
        $psychologists = PsychologistProfile::where('is_verified', true)
            ->where('is_available', true)
            ->with('user:id,name')
            ->get();

        $completedSessions = $request->user()
            ->testSessions()
            ->where('status', TestSessionStatus::Completed->value)
            ->with([
                'result.details' => fn ($q) => $q->orderBy('rank')->with('alternative:id,name'),
            ])
            ->latest('started_at')
            ->get()
            ->each(function ($session) {
                if ($session->result) {
                    $session->result->setRelation('topDetail', $session->result->details->first());
                    $session->result->unsetRelation('details');
                }
            });

        return Inertia::render('Consultations/Create', [
            'psychologists'     => $psychologists,
            'completedSessions' => $completedSessions,
            'lifePhaseOptions'  => LifePhase::options(),
        ]);
    }

    public function store(StoreConsultationRequest $request)
    {
        $consultation = $request->user()->consultations()->create([
            ...$request->validated(),
            'status' => ConsultationStatus::Pending->value,
        ]);

        $consultation->load('psychologistProfile.user');
        $consultation->psychologistProfile->user->notify(
            new ConsultationStatusUpdated($consultation, 'requested')
        );

        return redirect()->route('consultations.index')
            ->with('success', 'Permintaan konsultasi berhasil diajukan. Menunggu respon psikolog.');
    }

   public function show(Consultation $consultation, Request $request, MarkConsultationMessagesAsRead $markAsRead)
    {
        $this->authorizeOwnership($consultation, $request);

        if ($this->messagesVisible($consultation)) {
            $markAsRead->handle($consultation, $request->user());
        }

        $consultation->load('psychologistProfile.user:id,name', 'messages.sender:id,name');

        return Inertia::render('Consultations/Show', [
            'consultation' => $consultation,
        ]);
    }

    public function sendMessage(SendConsultationMessageRequest $request, Consultation $consultation)
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

        $consultation->loadMissing('psychologistProfile.user');
        $consultation->psychologistProfile->user->notify(new NewConsultationMessage($message));

        broadcast(new ConsultationMessageSent($message))->toOthers();

        return response()->json(['data' => $message]);
    }

    public function markAsRead(Consultation $consultation, Request $request, MarkConsultationMessagesAsRead $markAsRead)
    {
        $this->authorizeOwnership($consultation, $request);
        abort_unless($this->messagesVisible($consultation), 422);

        $markAsRead->handle($consultation, $request->user());

        return response()->json(['ok' => true]);
    }
    private function authorizeOwnership(Consultation $consultation, Request $request): void
    {
        abort_if($consultation->user_id !== $request->user()->id, 403);
    }
    private function messagesVisible(Consultation $consultation): bool
    {
        return in_array($consultation->status, [
            ConsultationStatus::Scheduled->value,
            ConsultationStatus::Completed->value,
        ], true);
    }
}
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
        $request->user()->consultations()->create([
            ...$request->validated(),
            'status' => ConsultationStatus::Pending->value,
        ]);

        return redirect()->route('consultations.index')
            ->with('success', 'Permintaan konsultasi berhasil diajukan. Menunggu respon psikolog.');
    }

    public function show(Consultation $consultation, Request $request)
    {
        $this->authorizeOwnership($consultation, $request);

        $consultation->load('psychologistProfile.user:id,name', 'messages.sender:id,name');

        return Inertia::render('Consultations/Show', [
            'consultation' => $consultation,
        ]);
    }
    public function sendMessage(SendConsultationMessageRequest $request, Consultation $consultation)
    {
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

        broadcast(new ConsultationMessageSent($message))->toOthers();

        return response()->json(['data' => $message]);
    }

    private function authorizeOwnership(Consultation $consultation, Request $request): void
    {
        abort_if($consultation->user_id !== $request->user()->id, 403);
    }
}
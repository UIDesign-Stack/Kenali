<?php

namespace App\Http\Controllers;

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
            ->where('status', 'completed')
            ->with(['result.details' => fn ($q) => $q->orderBy('rank')->limit(1)->with('alternative:id,name')])
            ->latest('started_at')
            ->get();

        return Inertia::render('Consultations/Create', [
            'psychologists'     => $psychologists,
            'completedSessions' => $completedSessions,
        ]);
    }

    public function store(StoreConsultationRequest $request)
    {
        $request->user()->consultations()->create([
            ...$request->validated(),
            'status' => 'pending',
        ]);

        return redirect()->route('consultations.index')
            ->with('success', 'Permintaan konsultasi berhasil diajukan. Menunggu respon psikolog.');
    }

    public function show(Consultation $consultation, Request $request)
    {
        abort_if($consultation->user_id !== $request->user()->id, 403);

        $consultation->load('psychologistProfile.user:id,name', 'messages.sender:id,name');

        return Inertia::render('Consultations/Show', [
            'consultation' => $consultation,
        ]);
    }

    public function sendMessage(SendConsultationMessageRequest $request, Consultation $consultation)
    {
        abort_if(
            $consultation->status !== 'scheduled',
            422,
            'Konsultasi ini belum/tidak bisa menerima pesan (status: '.$consultation->status.').'
        );

        $consultation->messages()->create([
            'sender_id' => $request->user()->id,
            'message'   => $request->validated('message'),
            'sent_at'   => now(),
        ]);

        return back();
    }
}
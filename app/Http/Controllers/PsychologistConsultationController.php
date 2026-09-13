<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendPsychologistMessageRequest;
use App\Http\Requests\UpdateConsultationStatusRequest;
use App\Models\Consultation;
use Illuminate\Http\Request;
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
        ]);
    }

    public function show(Consultation $consultation, Request $request)
    {
        abort_if($consultation->psychologistProfile->user_id !== $request->user()->id, 403);

        $consultation->load([
            'user:id,name,life_phase',
            'testSession.result.details' => fn ($q) => $q->orderBy('rank')->with('alternative:id,name'),
            'messages.sender:id,name',
        ]);

        return Inertia::render('Psychologist/Consultations/Show', [
            'consultation' => $consultation,
        ]);
    }

    public function updateStatus(UpdateConsultationStatusRequest $request, Consultation $consultation)
    {
        abort_if(
            in_array($consultation->status, ['completed', 'cancelled']),
            422,
            'Konsultasi ini sudah berstatus akhir dan tidak bisa diubah lagi.'
        );

        $validated = $request->validated();

        abort_if(
            $validated['status'] === 'completed' && $consultation->status !== 'scheduled',
            422,
            'Konsultasi harus dijadwalkan (scheduled) dulu sebelum bisa ditandai selesai.'
        );

        $consultation->update($validated);

        return back()->with('success', 'Status konsultasi diperbarui.');
    }

    public function sendMessage(SendPsychologistMessageRequest $request, Consultation $consultation)
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
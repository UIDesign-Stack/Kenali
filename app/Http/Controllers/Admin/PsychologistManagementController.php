<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConsultationStatus;
use App\Events\ConsultationStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\PsychologistProfile;
use App\Notifications\ConsultationStatusUpdated;
use App\Notifications\PsychologistVerified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function toggleVerified(PsychologistProfile $psychologistProfile)
    {
        $psychologistProfile->load('user:id,name');
        $name = $psychologistProfile->user->name ?? 'Psikolog';

        $isRevoking = $psychologistProfile->is_verified;

        if ($isRevoking) {
            $hasActiveConsultation = $psychologistProfile->consultations()
                ->whereIn('status', ConsultationStatus::activeStatusValues())
                ->exists();

            if ($hasActiveConsultation) {
                return back()->withErrors([
                    'psychologist' => "Verifikasi {$name} tidak bisa dibatalkan karena masih memiliki konsultasi yang sedang berjalan. Lihat & selesaikan/batalkan konsultasinya dulu.",
                ]);
            }
        }

        $psychologistProfile->update(['is_verified' => ! $psychologistProfile->is_verified]);

        if ($psychologistProfile->is_verified) {
            $psychologistProfile->user->notify(new PsychologistVerified());
        }

        return back()->with('success', $psychologistProfile->is_verified
            ? "Psikolog {$name} berhasil diverifikasi."
            : "Verifikasi psikolog {$name} dibatalkan.");
    }

    public function toggleAvailable(PsychologistProfile $psychologistProfile)
    {
        $psychologistProfile->load('user:id,name');
        $name = $psychologistProfile->user->name ?? 'Psikolog';

        $isTurningOff = $psychologistProfile->is_available;

        if ($isTurningOff) {
            $hasActiveConsultation = $psychologistProfile->consultations()
                ->whereIn('status', ConsultationStatus::activeStatusValues())
                ->exists();

            if ($hasActiveConsultation) {
                return back()->withErrors([
                    'psychologist' => "Psikolog {$name} tidak bisa dinonaktifkan karena masih memiliki konsultasi yang sedang berjalan. Lihat & selesaikan/batalkan konsultasinya dulu.",
                ]);
            }
        }

        $psychologistProfile->update(['is_available' => ! $psychologistProfile->is_available]);

        return back()->with('success', $psychologistProfile->is_available
            ? "Psikolog {$name} kini tersedia untuk konsultasi baru."
            : "Psikolog {$name} dinonaktifkan sementara dari konsultasi baru.");
    }

    public function consultations(PsychologistProfile $psychologistProfile)
    {
        $psychologistProfile->load('user:id,name');

        $consultations = $psychologistProfile->consultations()
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

        $wasAlreadyTerminal = DB::transaction(function () use ($consultation, $validated) {
            $locked = Consultation::whereKey($consultation->id)->lockForUpdate()->first();

            if (in_array($locked->status, ConsultationStatus::terminalStatusValues())) {
                return true;
            }

            $locked->update([
                'status'           => ConsultationStatus::Cancelled->value,
                'cancelled_reason' => '[Dibatalkan oleh admin] '.$validated['cancelled_reason'],
            ]);

            return false;
        });

        if ($wasAlreadyTerminal) {
            return back()->withErrors([
                'consultation' => 'Konsultasi ini sudah berstatus akhir, tidak bisa dibatalkan lagi.',
            ]);
        }

        $consultation->refresh()->loadMissing('user', 'psychologistProfile.user');
        $consultation->user->notify(new ConsultationStatusUpdated($consultation, 'force_cancelled'));
        $consultation->psychologistProfile->user->notify(new ConsultationStatusUpdated($consultation, 'force_cancelled'));

        broadcast(new ConsultationStatusChanged($consultation));

        return back()->with('success', 'Konsultasi berhasil dibatalkan oleh admin.');
    }
}
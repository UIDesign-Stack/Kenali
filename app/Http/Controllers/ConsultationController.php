<?php

namespace App\Http\Controllers;

use App\Enums\ConsultationStatus;
use App\Enums\LifePhase;
use App\Enums\TestSessionStatus;
use App\Events\ConsultationMessageSent;
use App\Http\Requests\SendConsultationMessageRequest;
use App\Http\Requests\StoreConsultationRequest;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Models\PsychologistProfile;
use App\Notifications\ConsultationStatusUpdated;
use App\Notifications\NewConsultationMessage;
use App\Actions\MarkConsultationMessagesAsRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ConsultationController extends Controller
{

    private const PROFILE_COLUMNS = [
        'id', 'user_id', 'specialization', 'bio', 'photo', 'years_of_experience',
        'is_available', 'is_verified', 'rating_avg', 'rating_count',
    ];

    public function index(Request $request)
    {
        $consultations = $request->user()
            ->consultations()
            ->with([
                $this->profileRelation(),
                'psychologistProfile.user:id,name',
                'review:id,consultation_id,rating',
            ])
            ->latest()
            ->get();

        return Inertia::render('Consultations/Index', [
            'consultations' => $consultations,
        ]);
    }

    public function create(Request $request)
    {
        $psychologists = PsychologistProfile::select(self::PROFILE_COLUMNS)
            ->where('is_verified', true)
            ->where('is_available', true)

            ->whereHas('user', fn ($q) => $q->where('is_active', true))
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
        $user      = $request->user();
        $validated = $request->validated();

        $outcome = DB::transaction(function () use ($user, $validated) {

            $profile = PsychologistProfile::whereKey($validated['psychologist_profile_id'])
                ->lockForUpdate()
                ->first();

            if (! $profile || ! $profile->is_verified || ! $profile->is_available || ! $profile->user?->is_active) {
                return ['error' => 'unavailable'];
            }

            $hasActive = $user->consultations()
                ->where('psychologist_profile_id', $profile->id)
                ->whereIn('status', ConsultationStatus::activeStatusValues())
                ->exists();

            if ($hasActive) {
                return ['error' => 'duplicate'];
            }

            $consultation = new Consultation($validated);
            $consultation->user_id = $user->id;
            $consultation->status  = ConsultationStatus::Pending->value;
            $consultation->save();

            return ['consultation' => $consultation];
        });

        if (isset($outcome['error'])) {
            $message = $outcome['error'] === 'duplicate'
                ? 'Anda sudah punya konsultasi yang masih berjalan dengan psikolog ini.'
                : 'Psikolog ini sedang tidak tersedia. Silakan pilih psikolog lain.';

            return back()->withInput()->withErrors(['psychologist_profile_id' => $message]);
        }

        $consultation = $outcome['consultation'];

        rescue(function () use ($consultation) {
            $consultation->load('psychologistProfile.user');
            $consultation->psychologistProfile?->user?->notify(
                new ConsultationStatusUpdated($consultation, 'requested')
            );
        });

        return redirect()->route('consultations.index')
            ->with('success', 'Permintaan konsultasi berhasil diajukan. Menunggu respon psikolog.');
    }

    public function show(Consultation $consultation, Request $request, MarkConsultationMessagesAsRead $markAsRead)
    {
        $this->authorizeOwnership($consultation, $request);

        if ($this->messagesVisible($consultation)) {
            $markAsRead->handle($consultation, $request->user());
        }

        $consultation->load([
            $this->profileRelation(),
            'psychologistProfile.user:id,name',
            'messages.sender:id,name',
            'review',
        ]);

        return Inertia::render('Consultations/Show', [
            'consultation'   => $consultation,
            'reviewEditable' => $consultation->review?->isEditableByAuthor() ?? false,
            'closesAt'       => $consultation->autoCloseAt()?->toIso8601String(),
        ]);
    }

    public function sendMessage(SendConsultationMessageRequest $request, Consultation $consultation)
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
            $consultation->loadMissing('psychologistProfile.user');
            $consultation->psychologistProfile?->user?->notify(new NewConsultationMessage($message));
        });

        rescue(fn () => broadcast(new ConsultationMessageSent($message))->toOthers());

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
        abort_if((int) $consultation->user_id !== (int) $request->user()->id, 404);
    }

    private function messagesVisible(Consultation $consultation): bool
    {
        return in_array($consultation->status, [
            ConsultationStatus::Scheduled->value,
            ConsultationStatus::Completed->value,
        ], true);
    }

    private function profileRelation(): string
    {
        return 'psychologistProfile:'.implode(',', self::PROFILE_COLUMNS);
    }
}

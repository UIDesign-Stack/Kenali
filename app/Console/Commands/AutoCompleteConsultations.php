<?php

namespace App\Console\Commands;

use App\Enums\ConsultationStatus;
use App\Events\ConsultationStatusChanged;
use App\Models\Consultation;
use App\Notifications\ConsultationStatusUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCompleteConsultations extends Command
{
    protected $signature = 'consultations:auto-complete
                        {--warn-days=5 : Hari tanpa aktivitas sebelum peringatan}
                        {--days=7 : Hari tanpa aktivitas sebelum ditutup}';

    protected $description = 'Peringatkan lalu tutup konsultasi terjadwal yang tidak ada aktivitas sama sekali';

    public function handle(): int
    {
        $warnDays = max(1, (int) $this->option('warn-days'));
        $closeDays = max($warnDays + 1, (int) $this->option('days'));

        $closeCutoff = now()->subDays($closeDays);
        $warnCutoff = now()->subDays($warnDays);

        $warned = 0;
        $closed = 0;

        $candidates = Consultation::where('status', ConsultationStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', $warnCutoff)
            ->withMax('messages', 'sent_at')
            ->get();

        foreach ($candidates as $c) {
            $last = collect([$c->scheduled_at, $c->messages_max_sent_at])
                ->filter()
                ->map(fn ($d) => \Illuminate\Support\Carbon::parse($d))
                ->max();

            if ($last->gt($warnCutoff)) {
                continue; // masih ada aktivitas
            }

            if ($last->lte($closeCutoff)) {
                $closed += $this->close($c->id) ? 1 : 0;
            } elseif ($this->warn_once($c->id)) {
                $warned++;
            }
        }

        $this->info("{$warned} peringatan terkirim, {$closed} konsultasi ditutup.");

        return self::SUCCESS;
    }

    private function warn_once(int $id): bool
    {
        $c = Consultation::with('user', 'psychologistProfile.user')->find($id);

        // Hindari peringatan berulang tiap jam
        $already = $c->user->notifications()
            ->where('type', ConsultationStatusUpdated::class)
            ->where('data->consultation_id', $c->id)
            ->where('data->title', 'Konsultasi akan ditutup otomatis')
            ->exists();

        if ($already) {
            return false;
        }

        $c->user->notify(new ConsultationStatusUpdated($c, 'closing_soon'));
        $c->psychologistProfile->user->notify(new ConsultationStatusUpdated($c, 'closing_soon'));

        return true;
    }

    private function close(int $id): bool
    {
        $consultation = DB::transaction(function () use ($id) {
            $locked = Consultation::whereKey($id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== ConsultationStatus::Scheduled->value) {
                return null;
            }

            $locked->update(['status' => ConsultationStatus::Completed->value]);

            return $locked;
        });

        if (! $consultation) {
            return false;
        }

        $consultation->loadMissing('user', 'psychologistProfile.user');
        $consultation->user->notify(new ConsultationStatusUpdated($consultation, 'auto_completed'));
        $consultation->psychologistProfile->user->notify(new ConsultationStatusUpdated($consultation, 'auto_completed_psychologist'));

        broadcast(new ConsultationStatusChanged($consultation));

        return true;
    }
}

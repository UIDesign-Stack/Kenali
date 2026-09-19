<?php

namespace App\Console\Commands;

use App\Enums\ConsultationStatus;
use App\Models\Consultation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RevertOverdueConsultations extends Command
{
    protected $signature = 'consultations:revert-overdue';

    protected $description = 'Kembalikan konsultasi berstatus "scheduled" yang jadwalnya sudah lewat tanpa ditandai selesai, jadi "pending" lagi.';

    public function handle(): int
    {
        $revertedCount = DB::transaction(function () {
            $overdueConsultations = Consultation::where('status', ConsultationStatus::Scheduled->value)
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<', now()->subHours(2))
                ->lockForUpdate()
                ->get();

            foreach ($overdueConsultations as $consultation) {
                if ($consultation->status !== ConsultationStatus::Scheduled->value) {
                    continue;
                }

                $consultation->update([
                    'status'       => ConsultationStatus::Pending->value,
                    'scheduled_at' => null,
                    'notes'        => trim(($consultation->notes ?? '')."\n[Sistem] Jadwal sebelumnya (".$consultation->getOriginal('scheduled_at').") terlewat tanpa ditandai selesai, dikembalikan ke antrean pending."),
                ]);

                $this->line("Konsultasi #{$consultation->id} dikembalikan ke pending.");
            }

            return $overdueConsultations->count();
        });

        if ($revertedCount === 0) {
            $this->info('Tidak ada konsultasi yang perlu dikembalikan statusnya.');
        } else {
            $this->info($revertedCount.' konsultasi berhasil dikembalikan ke status pending.');
        }

        return self::SUCCESS;
    }
}
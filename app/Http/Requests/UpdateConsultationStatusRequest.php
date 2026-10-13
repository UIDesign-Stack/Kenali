<?php

namespace App\Http\Requests;

use App\Enums\ConsultationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateConsultationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('consultation');
        $profileId    = $this->user()?->psychologistProfile?->id;

        return $consultation !== null
            && $profileId !== null
            && (int) $consultation->psychologist_profile_id === (int) $profileId;
    }


    protected function failedAuthorization(): void
    {
        throw new NotFoundHttpException();
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                new Enum(ConsultationStatus::class),
                Rule::in([
                    ConsultationStatus::Scheduled->value,
                    ConsultationStatus::Completed->value,
                    ConsultationStatus::Cancelled->value,
                ]),
            ],

            'scheduled_at'     => ['nullable', 'date', 'after:now', 'before:+1 year', 'required_if:status,'.ConsultationStatus::Scheduled->value],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'cancelled_reason' => ['nullable', 'string', 'max:1000', 'required_if:status,'.ConsultationStatus::Cancelled->value],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after'           => 'Jadwal konsultasi harus di waktu yang akan datang.',
            'scheduled_at.before'          => 'Jadwal konsultasi tidak boleh lebih dari satu tahun ke depan.',
            'scheduled_at.required_if'     => 'Jadwal wajib diisi kalau status diubah menjadi terjadwal.',
            'cancelled_reason.required_if' => 'Alasan pembatalan wajib diisi.',
        ];
    }

    public function attributes(): array
    {
        return [
            'status'           => 'status',
            'scheduled_at'     => 'jadwal',
            'notes'            => 'catatan',
            'cancelled_reason' => 'alasan pembatalan',
        ];
    }
}

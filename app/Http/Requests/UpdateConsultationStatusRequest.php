<?php

namespace App\Http\Requests;

use App\Enums\ConsultationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateConsultationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('consultation');

        return $consultation?->psychologistProfile?->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [

            'status' => ['required', new Enum(ConsultationStatus::class), 'in:'.implode(',', [
                ConsultationStatus::Scheduled->value,
                ConsultationStatus::Completed->value,
                ConsultationStatus::Cancelled->value,
            ])],
            'scheduled_at'     => ['nullable', 'date', 'after:now', 'required_if:status,'.ConsultationStatus::Scheduled->value],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'cancelled_reason' => ['nullable', 'string', 'max:1000', 'required_if:status,'.ConsultationStatus::Cancelled->value],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'Jadwal konsultasi harus di waktu yang akan datang.',
            'scheduled_at.required_if' => 'Jadwal wajib diisi kalau status diubah menjadi terjadwal.',
            'cancelled_reason.required_if' => 'Alasan pembatalan wajib diisi.',
        ];
    }
}
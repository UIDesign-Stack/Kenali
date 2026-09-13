<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('consultation');

        return $consultation->psychologistProfile->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'status'           => ['required', 'in:scheduled,completed,cancelled'],
            'scheduled_at'     => ['nullable', 'date', 'after:now', 'required_if:status,scheduled'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'cancelled_reason' => ['nullable', 'string', 'max:1000', 'required_if:status,cancelled'],
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
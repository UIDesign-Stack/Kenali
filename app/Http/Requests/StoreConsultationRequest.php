<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'psychologist_profile_id' => [
                'required',
                Rule::exists('psychologist_profiles', 'id')
                    ->where('is_verified', true)
                    ->where('is_available', true),
            ],
            'test_session_id' => [
                'nullable',
                Rule::exists('test_sessions', 'id')->where('user_id', $this->user()->id),
            ],
            'type'  => ['required', 'in:chat,video_call'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'psychologist_profile_id.exists' => 'Psikolog yang dipilih tidak tersedia atau belum terverifikasi.',
            'test_session_id.exists'         => 'Sesi tes tidak ditemukan atau bukan milikmu.',
        ];
    }
}
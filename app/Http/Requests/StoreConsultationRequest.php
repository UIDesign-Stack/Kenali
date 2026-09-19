<?php

namespace App\Http\Requests;

use App\Enums\TestSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'type'  => ['required', 'in:chat,tatap_muka'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (empty($this->test_session_id)) {
                return;
            }

            $isCompleted = $this->user()
                ->testSessions()
                ->where('id', $this->test_session_id)
                ->where('status', TestSessionStatus::Completed->value)
                ->exists();

            if (! $isCompleted) {
                $validator->errors()->add(
                    'test_session_id',
                    'Sesi tes yang dipilih belum selesai dikerjakan.'
                );
            }
        });
    }
}
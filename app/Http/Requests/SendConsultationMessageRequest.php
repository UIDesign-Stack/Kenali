<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendConsultationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('consultation');

        return $consultation->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SendPsychologistMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'pesan',
        ];
    }
}

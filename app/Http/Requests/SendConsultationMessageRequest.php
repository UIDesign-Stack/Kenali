<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SendConsultationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultation = $this->route('consultation');

        return (int) $consultation->user_id === (int) $this->user()->id;
    }

    /**
     * Bukan pemilik mendapat 404 (bukan 403), sama dengan ConsultationController::authorizeOwnership(),
     * supaya orang lain tidak bisa memastikan ID konsultasi mana yang ada.
     */
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

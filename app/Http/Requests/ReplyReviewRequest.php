<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('psikolog') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $reply = $this->input('reply');

        $this->merge([
            'reply' => is_string($reply)
                ? trim(preg_replace('/[ \t]+/u', ' ', $reply))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['reply' => 'balasan'];
    }
}
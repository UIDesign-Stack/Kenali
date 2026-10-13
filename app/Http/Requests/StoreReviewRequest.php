<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {

        return $this->user()?->hasRole('user') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $comment = $this->input('comment');

        $normalized = null;

        if (is_string($comment)) {

            $collapsed = preg_replace('/[ \t]+/u', ' ', $comment);

            if (is_string($collapsed)) {
                $normalized = trim($collapsed) ?: null;
            }
        }

        $this->merge(['comment' => $normalized]);
    }

    public function rules(): array
    {
        return [
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'min:3', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rating'  => 'rating',
            'comment' => 'komentar',
        ];
    }
}

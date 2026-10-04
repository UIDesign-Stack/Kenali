<?php

namespace App\Http\Requests\Admin;

use App\Enums\LifePhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class AlternativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // BARU: rapikan input sebelum divalidasi
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'        => is_string($this->name) ? preg_replace('/\s+/u', ' ', trim($this->name)) : $this->name,
            'description' => is_string($this->description) ? trim($this->description) : $this->description,
            'icon'        => is_string($this->icon) ? trim($this->icon) : $this->icon,
        ]);
    }

    public function rules(): array
    {
        $alternative = $this->route('alternative');

        return [
            'name' => [
                'required',
                'string',
                'min:2', // BARU
                'max:255',
                Rule::unique('alternatives', 'name')->ignore($alternative?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon'        => ['nullable', 'string', 'max:50', 'regex:/^[\p{L}\p{N}\p{So}\p{Sk}\p{M}_\- ]+$/u'],
            'life_phase'  => ['required', new Enum(LifePhase::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'        => 'nama',
            'description' => 'deskripsi',
            'icon'        => 'ikon',
            'life_phase'  => 'fase kehidupan',
        ];
    }
}
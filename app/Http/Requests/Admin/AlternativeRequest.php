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

    public function rules(): array
    {
        $alternative = $this->route('alternative');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('alternatives', 'name')->ignore($alternative?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon'        => ['nullable', 'string', 'max:255'],
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
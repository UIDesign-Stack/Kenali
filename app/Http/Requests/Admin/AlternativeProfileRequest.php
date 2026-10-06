<?php

namespace App\Http\Requests\Admin;

use App\Models\SubCriteria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AlternativeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'scores'                   => ['required', 'array', 'min:1', 'max:200'],
            'scores.*.sub_criteria_id' => ['required', 'integer', 'distinct', 'exists:sub_criteria,id'],
            'scores.*.ideal_score'     => ['required', 'numeric', 'between:1,5', 'decimal:0,2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'scores.*.sub_criteria_id' => 'sub-kriteria',
            'scores.*.ideal_score'     => 'skor ideal',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $submittedIds = collect($this->input('scores', []))
                ->pluck('sub_criteria_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $missing = array_diff(SubCriteria::pluck('id')->all(), $submittedIds);

            if (! empty($missing)) {
                $validator->errors()->add(
                    'scores',
                    'Semua sub-kriteria harus diberi skor ideal. Ada '.count($missing).' sub-kriteria yang belum diisi.'
                );
            }
        });
    }
}

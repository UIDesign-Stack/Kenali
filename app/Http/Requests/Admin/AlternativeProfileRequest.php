<?php

namespace App\Http\Requests\Admin;

use App\Models\SubCriteria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AlternativeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scores'                    => ['required', 'array', 'min:1'],
            'scores.*.sub_criteria_id'  => ['required', 'integer', 'distinct', 'exists:sub_criteria,id'],
            'scores.*.ideal_score'      => ['required', 'numeric', 'min:1', 'max:5'],
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
            $submittedIds = collect($this->input('scores', []))
                ->pluck('sub_criteria_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            $allSubCriteriaIds = SubCriteria::pluck('id')->all();

            $missing = array_diff($allSubCriteriaIds, $submittedIds);

            if (! empty($missing)) {
                $validator->errors()->add(
                    'scores',
                    'Semua sub-kriteria harus diberi skor ideal. Ada '.count($missing).' sub-kriteria yang belum diisi.'
                );
            }
        });
    }
}
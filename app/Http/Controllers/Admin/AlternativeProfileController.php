<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlternativeProfileRequest;
use App\Models\Alternative;
use App\Models\AlternativeProfile;
use App\Models\Criteria;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AlternativeProfileController extends Controller
{
    public function edit(Alternative $alternative)
    {
        $criteria = Criteria::with(['subCriteria' => fn ($q) => $q->orderBy('order')])
            ->orderBy('order')
            ->get();

        $existingScores = $alternative->profiles()
            ->pluck('ideal_score', 'sub_criteria_id');

        return Inertia::render('Admin/Alternatives/ProfileEdit', [
            'alternative'    => $alternative->only('id', 'name'),
            'criteria'       => $criteria,
            'existingScores' => $existingScores,
        ]);
    }

    public function update(AlternativeProfileRequest $request, Alternative $alternative)
    {
        $now = now();

        $rows = collect($request->validated('scores'))
            ->map(fn (array $score) => [
                'alternative_id'  => $alternative->id,
                'sub_criteria_id' => $score['sub_criteria_id'],
                'ideal_score'     => $score['ideal_score'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ])
            ->all();

        DB::transaction(function () use ($alternative, $rows) {
            AlternativeProfile::upsert(
                $rows,
                uniqueBy: ['alternative_id', 'sub_criteria_id'],
                update: ['ideal_score', 'updated_at']
            );
        });

        return redirect()
            ->route('admin.alternatives.index')
            ->with('success', "Profil ideal \"{$alternative->name}\" berhasil disimpan.");
    }
}
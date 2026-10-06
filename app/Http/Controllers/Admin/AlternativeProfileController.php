<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlternativeProfileRequest;
use App\Models\Alternative;
use App\Models\AlternativeProfile;
use App\Models\Criteria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AlternativeProfileController extends Controller
{
    public function edit(Alternative $alternative)
    {
        $criteria = Criteria::select('id', 'code', 'name', 'order')
            ->with(['subCriteria' => fn ($q) => $q->select('id', 'criteria_id', 'code', 'name', 'order')->orderBy('order')])
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
                'sub_criteria_id' => (int) $score['sub_criteria_id'],
                'ideal_score'     => $score['ideal_score'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ])
            ->all();

        $newScores = collect($rows)
            ->pluck('ideal_score', 'sub_criteria_id')
            ->map(fn ($value) => (float) $value);

        $audit = DB::transaction(function () use ($alternative, $rows, $newScores) {
            Alternative::whereKey($alternative->id)->lockForUpdate()->firstOrFail();

            $oldScores = AlternativeProfile::where('alternative_id', $alternative->id)
                ->pluck('ideal_score', 'sub_criteria_id')
                ->map(fn ($value) => (float) $value);

            AlternativeProfile::upsert(
                $rows,
                uniqueBy: ['alternative_id', 'sub_criteria_id'],
                update: ['ideal_score', 'updated_at']
            );

            AlternativeProfile::where('alternative_id', $alternative->id)
                ->whereNotIn('sub_criteria_id', $newScores->keys()->all())
                ->delete();

            return [
                'changed' => $newScores
                    ->filter(fn ($score, $id) => ! $oldScores->has($id) || abs($oldScores[$id] - $score) > 0.0001)
                    ->map(fn ($score, $id) => ['old' => $oldScores->get($id), 'new' => $score])
                    ->all(),
                'removed' => $oldScores->keys()->diff($newScores->keys())->values()->all(),
            ];
        });

        if ($audit['changed'] !== [] || $audit['removed'] !== []) {
            Log::info('Profil ideal alternatif diubah', [
                'alternative_id' => $alternative->id,
                'admin_id'       => $request->user()->id,
                'changed'        => $audit['changed'],
                'removed'        => $audit['removed'],
            ]);
        }

        return redirect()
            ->route('admin.alternatives.index')
            ->with('success', "Profil ideal \"{$alternative->name}\" berhasil disimpan.");
    }
}

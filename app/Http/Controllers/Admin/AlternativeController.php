<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LifePhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlternativeRequest;
use App\Models\Alternative;
use App\Models\SubCriteria;
use Inertia\Inertia;

class AlternativeController extends Controller
{
    public function index()
    {
        $alternatives = Alternative::withCount('profiles')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Alternatives/Index', [
            'alternatives'     => $alternatives,
            'lifePhaseOptions' => LifePhase::options(),
            'totalSubCriteria' => SubCriteria::count(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Alternatives/Create', [
            'lifePhaseOptions' => LifePhase::options(),
        ]);
    }

    public function store(AlternativeRequest $request)
    {
        $alternative = Alternative::create($request->validated());

        return redirect()
            ->route('admin.alternatives.index')
            ->with('success', "Alternatif \"{$alternative->name}\" berhasil ditambahkan. Lanjutkan atur profil idealnya.");
    }

    public function edit(Alternative $alternative)
    {
        return Inertia::render('Admin/Alternatives/Edit', [
            'alternative'      => $alternative,
            'lifePhaseOptions' => LifePhase::options(),
        ]);
    }

    public function update(AlternativeRequest $request, Alternative $alternative)
    {
        $alternative->update($request->validated());

        return redirect()
            ->route('admin.alternatives.index')
            ->with('success', "Alternatif \"{$alternative->name}\" berhasil diperbarui.");
    }

    public function toggleActive(Alternative $alternative)
    {
        $alternative->update(['is_active' => ! $alternative->is_active]);

        $status = $alternative->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Alternatif \"{$alternative->name}\" berhasil {$status}.");
    }

    public function destroy(Alternative $alternative)
    {
        if ($alternative->resultDetails()->exists()) {
            return back()->withErrors([
                'alternative' => 'Alternatif ini tidak bisa dihapus karena sudah pernah muncul di hasil tes user. Nonaktifkan saja.',
            ]);
        }

        $alternative->delete();

        return back()->with('success', 'Alternatif berhasil dihapus.');
    }
}
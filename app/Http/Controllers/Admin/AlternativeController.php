<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LifePhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlternativeRequest;
use App\Models\Alternative;
use App\Models\SubCriteria;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        Log::info('Alternatif diperbarui', [
            'alternative_id' => $alternative->id,
            'admin_id'       => $request->user()->id,
            'old' => $alternative->only(['name', 'description', 'icon', 'life_phase']),
            'new'            => $request->validated(),
        ]);
        $alternative->update($request->validated());

        return redirect()
            ->route('admin.alternatives.index')
            ->with('success', "Alternatif \"{$alternative->name}\" berhasil diperbarui.");
    }

    public function toggleActive(Alternative $alternative)
    {
        $result = DB::transaction(function () use ($alternative) {
            $locked = Alternative::whereKey($alternative->id)->lockForUpdate()->first();

            if ($locked->is_active) {
                if (Alternative::where('is_active', true)->count() <= 1) {
                    return 'last_active';
                }
            } elseif ($locked->profiles()->count() < SubCriteria::count()) {
                return 'incomplete_profile';
            }

            $locked->update(['is_active' => ! $locked->is_active]);

            return $locked->is_active ? 'diaktifkan' : 'dinonaktifkan';
        });

        if ($result === 'last_active') {
            return back()->withErrors(['alternative' => 'Minimal harus ada satu alternatif aktif.']);
        }
        if ($result === 'incomplete_profile') {
            return back()->withErrors(['alternative' => 'Profil ideal belum lengkap. Lengkapi semua sub-kriteria sebelum mengaktifkan.']);
        }

        return back()->with('success', "Alternatif \"{$alternative->name}\" berhasil {$result}.");
    }

    public function destroy(Alternative $alternative)
    {
        $deleted = DB::transaction(function () use ($alternative) {
            $locked = Alternative::whereKey($alternative->id)->lockForUpdate()->first();

            if (! $locked || $locked->resultDetails()->exists()) {
                return false;
            }

            $locked->profiles()->delete();
            $locked->delete();

            return true;
        });

        if (! $deleted) {
            return back()->withErrors([
                'alternative' => 'Alternatif ini tidak bisa dihapus karena sudah pernah muncul di hasil tes user. Nonaktifkan saja.',
            ]);
        }

        return back()->with('success', 'Alternatif berhasil dihapus.');
    }
}
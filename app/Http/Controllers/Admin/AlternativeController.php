<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LifePhase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AlternativeRequest;
use App\Models\Alternative;
use App\Models\SubCriteria;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $alternative = new Alternative($request->validated());
        $alternative->is_active = false;
        $alternative->save();

        Log::info('Alternatif ditambahkan', [
            'alternative_id' => $alternative->id,
            'admin_id'       => $request->user()->id,
            'name'           => $alternative->name,
        ]);

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
        // is_active diabaikan di sini: hanya toggleActive() yang boleh mengubahnya.
        $alternative->fill(Arr::except($request->validated(), ['is_active']));

        $changes  = $alternative->getDirty();
        $original = Arr::only($alternative->getRawOriginal(), array_keys($changes));

        $alternative->save();

        if ($changes !== []) {
            Log::info('Alternatif diperbarui', [
                'alternative_id' => $alternative->id,
                'admin_id'       => $request->user()->id,
                'old'            => $original,
                'new'            => $changes,
            ]);
        }

        return redirect()
            ->route('admin.alternatives.index')
            ->with('success', "Alternatif \"{$alternative->name}\" berhasil diperbarui.");
    }

    public function toggleActive(Request $request, Alternative $alternative)
    {
        $result = DB::transaction(function () use ($alternative) {
            [$exists, $activeCount] = $this->lockAllAlternatives($alternative->id);

            if (! $exists) {
                return 'not_found';
            }

            $locked = Alternative::whereKey($alternative->id)->lockForUpdate()->first();

            if ($locked->is_active) {
                if ($activeCount <= 1) {
                    return 'last_active';
                }
                $newState = false;
            } else {
                if (! $this->hasCompleteProfile($locked)) {
                    return 'incomplete_profile';
                }
                $newState = true;
            }

            $locked->forceFill(['is_active' => $newState])->save();

            return $newState ? 'diaktifkan' : 'dinonaktifkan';
        });

        if ($result === 'not_found') {
            return back()->withErrors(['alternative' => 'Alternatif tidak ditemukan.']);
        }
        if ($result === 'last_active') {
            return back()->withErrors(['alternative' => 'Minimal harus ada satu alternatif aktif.']);
        }
        if ($result === 'incomplete_profile') {
            return back()->withErrors(['alternative' => 'Profil ideal belum lengkap. Lengkapi semua sub-kriteria sebelum mengaktifkan.']);
        }

        Log::info('Status alternatif diubah', [
            'alternative_id' => $alternative->id,
            'admin_id'       => $request->user()->id,
            'status'         => $result,
        ]);

        return back()->with('success', "Alternatif \"{$alternative->name}\" berhasil {$result}.");
    }

    public function destroy(Request $request, Alternative $alternative)
    {
        $result = DB::transaction(function () use ($alternative) {
            [$exists, $activeCount] = $this->lockAllAlternatives($alternative->id);

            if (! $exists) {
                return 'not_found';
            }

            $locked = Alternative::whereKey($alternative->id)->lockForUpdate()->first();

            if ($locked->resultDetails()->exists()) {
                return 'has_results';
            }

            if ($locked->is_active && $activeCount <= 1) {
                return 'last_active';
            }

            $locked->profiles()->delete();
            $locked->delete();

            return 'deleted';
        });

        if ($result === 'has_results') {
            return back()->withErrors([
                'alternative' => 'Alternatif ini tidak bisa dihapus karena sudah pernah muncul di hasil tes user. Nonaktifkan saja.',
            ]);
        }
        if ($result === 'last_active') {
            return back()->withErrors(['alternative' => 'Minimal harus ada satu alternatif aktif.']);
        }
        if ($result === 'not_found') {
            return back()->withErrors(['alternative' => 'Alternatif tidak ditemukan.']);
        }

        Log::info('Alternatif dihapus', [
            'alternative_id' => $alternative->id,
            'admin_id'       => $request->user()->id,
            'name'           => $alternative->name,
        ]);

        return back()->with('success', 'Alternatif berhasil dihapus.');
    }

    /**
     * Kunci SEMUA baris alternatif (urut id agar tidak deadlock), bukan hanya satu baris.
     * Tanpa ini, dua admin yang menonaktifkan/menghapus dua alternatif berbeda secara
     * bersamaan sama-sama melihat "masih ada 2 aktif" dan keduanya lolos, sehingga
     * jumlah alternatif aktif bisa jadi nol. Harus dipanggil di dalam DB::transaction.
     *
     * @return array{0: bool, 1: int}  [apakah alternatif target ada, jumlah alternatif aktif]
     */
    protected function lockAllAlternatives(int $targetId): array
    {
        $all = Alternative::query()
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'is_active']);

        return [
            $all->contains('id', $targetId),
            $all->where('is_active', true)->count(),
        ];
    }

    /**
     * Profil dianggap lengkap kalau tiap sub-kriteria punya minimal satu profil.
     * Dihitung per sub_criteria_id unik, bukan jumlah baris, supaya baris ganda
     * tidak menutupi sub-kriteria yang belum diisi.
     */
    protected function hasCompleteProfile(Alternative $alternative): bool
    {
        $required = SubCriteria::count();
        $filled   = $alternative->profiles()->distinct()->count('sub_criteria_id');

        return $filled >= $required;
    }
}

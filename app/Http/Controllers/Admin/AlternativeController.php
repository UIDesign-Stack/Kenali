<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alternative;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlternativeController extends Controller
{
    public function index()
    {
        $alternatives = Alternative::withCount('profiles')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Alternatives/Index', [
            'alternatives' => $alternatives,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Alternatives/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon'        => ['nullable', 'string', 'max:255'],
            'life_phase'  => ['required', 'in:siswa,mahasiswa,pekerja,umum'],
        ]);

        $alternative = Alternative::create($validated);

        return redirect()
            ->route('admin.alternatives.index')
            ->with('success', "Alternatif \"{$alternative->name}\" berhasil ditambahkan. Lanjutkan atur profil idealnya.");
    }

    public function edit(Alternative $alternative)
    {
        return Inertia::render('Admin/Alternatives/Edit', [
            'alternative' => $alternative,
        ]);
    }

    public function update(Request $request, Alternative $alternative)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon'        => ['nullable', 'string', 'max:255'],
            'life_phase'  => ['required', 'in:siswa,mahasiswa,pekerja,umum'],
        ]);

        $alternative->update($validated);

        return redirect()
            ->route('admin.alternatives.index')
            ->with('success', "Alternatif \"{$alternative->name}\" berhasil diperbarui.");
    }

    public function toggleActive(Alternative $alternative)
    {
        $alternative->update(['is_active' => ! $alternative->is_active]);

        return back()->with('success', 'Status alternatif diperbarui.');
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
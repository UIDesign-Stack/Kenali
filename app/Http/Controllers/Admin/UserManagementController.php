<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();

        $query = User::with('roles:id,name')
            ->withCount('testSessions')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhere('email', 'like', '%'.addcslashes($search, '%_\\').'%');
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function show(User $user)
    {
        $user->load('roles:id,name', 'psychologistProfile');

        $testSessions = $user->testSessions()
            ->with([
                'result.details' => fn ($q) => $q->orderBy('rank')->with('alternative:id,name'),
            ])
            ->latest('started_at')
            ->get()
            ->each(function ($session) {
                if ($session->result) {
                    $session->result->setRelation('topDetail', $session->result->details->first());
                    $session->result->unsetRelation('details');
                }
            });

        return Inertia::render('Admin/Users/Show', [
            'user'         => $user,
            'testSessions' => $testSessions,
        ]);
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors([
                'user' => 'Tidak bisa menonaktifkan akun sendiri.',
            ]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? "Akun {$user->name} diaktifkan kembali."
            : "Akun {$user->name} dinonaktifkan.");
    }
}
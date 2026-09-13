<?php

namespace App\Http\Controllers;

use App\Models\Alternative;
use App\Models\Consultation;
use App\Models\PsychologistProfile;
use App\Models\TestResultDetail;
use App\Models\TestSession;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Precedence role sengaja diurutkan: admin > psikolog > user.
        // Kalau suatu saat ada user dengan lebih dari satu role (kasus tidak umum,
        // tapi bisa terjadi), dashboard admin yang diprioritaskan ditampilkan.
        if ($user->hasRole('admin')) {
            return Inertia::render('Dashboard', [
                'role' => 'admin',
                ...$this->adminData(),
            ]);
        }

        if ($user->hasRole('psikolog')) {
            return Inertia::render('Dashboard', [
                'role' => 'psikolog',
                ...$this->psikologData($user),
            ]);
        }

        return Inertia::render('Dashboard', [
            'role' => 'user',
            ...$this->userData($user),
        ]);
    }

    protected function adminData(): array
    {
        $stats = [
            'total_users'            => User::role('user')->count(),
            'active_users'           => User::role('user')->where('is_active', true)->count(),
            'total_psychologists'    => PsychologistProfile::count(),
            'verified_psychologists' => PsychologistProfile::where('is_verified', true)->count(),
            'total_test_sessions'       => TestSession::count(),
            'completed_test_sessions'   => TestSession::where('status', 'completed')->count(),
            'in_progress_test_sessions' => TestSession::where('status', 'in_progress')->count(),
            'total_consultations'     => Consultation::count(),
            'pending_consultations'   => Consultation::where('status', 'pending')->count(),
            'scheduled_consultations' => Consultation::where('status', 'scheduled')->count(),
            'completed_consultations' => Consultation::where('status', 'completed')->count(),
        ];

        $topAlternatives = TestResultDetail::where('rank', 1)
            ->selectRaw('alternative_id, COUNT(*) as total')
            ->groupBy('alternative_id')
            ->orderByDesc('total')
            ->with('alternative:id,name')
            ->limit(5)
            ->get();

        $maxCount = $topAlternatives->max('total') ?: 1;
        $topAlternatives = $topAlternatives->map(fn ($item) => [
            'name'    => $item->alternative->name ?? 'Tidak diketahui',
            'total'   => $item->total,
            'percent' => round(($item->total / $maxCount) * 100),
        ]);

        $recentSessions = TestSession::with('user:id,name')
            ->latest('started_at')
            ->limit(5)
            ->get(['id', 'user_id', 'life_phase', 'status', 'started_at']);

        return [
            'stats'           => $stats,
            'topAlternatives' => $topAlternatives,
            'recentSessions'  => $recentSessions,
        ];
    }

    protected function psikologData(User $user): array
    {
        $profile = $user->psychologistProfile;

        if (! $profile) {
            return ['profileMissing' => true];
        }

        return [
            'profile' => $profile,
            'stats' => [
                'pending_consultations'   => $profile->consultations()->where('status', 'pending')->count(),
                'scheduled_consultations' => $profile->consultations()->where('status', 'scheduled')->count(),
                'completed_consultations' => $profile->consultations()->where('status', 'completed')->count(),
            ],
            'recentConsultations' => $profile->consultations()
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    protected function userData(User $user): array
    {
        $latestSession = $user->testSessions()
            ->with(['result.details' => fn ($q) => $q->orderBy('rank')->limit(1)->with('alternative:id,name')])
            ->latest('started_at')
            ->first();

        return [
            'stats' => [
                'total_tests'         => $user->testSessions()->count(),
                'completed_tests'     => $user->testSessions()->where('status', 'completed')->count(),
                'total_consultations' => $user->consultations()->count(),
            ],
            'latestSession' => $latestSession,
        ];
    }
}
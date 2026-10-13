<script setup>
import { ref, onMounted } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    role: { type: String, required: true },
    stats: { type: Object, default: () => ({}) },
    topAlternatives: { type: Array, default: () => [] },
    recentSessions: { type: Array, default: () => [] },
    profile: { type: Object, default: null },
    recentConsultations: { type: Array, default: () => [] },
    latestSession: { type: Object, default: null },
    profileMissing: { type: Boolean, default: false },
});

const statusLabel = {
    in_progress: 'Sedang berjalan',
    completed: 'Selesai',
    pending: 'Menunggu respon',
    scheduled: 'Terjadwal',
    cancelled: 'Dibatalkan',
};

// ---- UI only: warna badge & titik timeline ----
const statusColor = {
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    in_progress: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    scheduled: 'bg-sky-50 text-sky-700 ring-sky-600/20',
    cancelled: 'bg-gray-100 text-gray-500 ring-gray-500/20',
};

const statusDot = {
    completed: 'bg-emerald-500',
    in_progress: 'bg-amber-400',
    pending: 'bg-amber-400',
    scheduled: 'bg-sky-500',
    cancelled: 'bg-gray-400',
};

const barColors = ['bg-indigo-500', 'bg-teal-500', 'bg-amber-400', 'bg-rose-400', 'bg-sky-500', 'bg-violet-500'];

const avatarColors = [
    'bg-indigo-100 text-indigo-700',
    'bg-teal-100 text-teal-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-sky-100 text-sky-700',
    'bg-violet-100 text-violet-700',
];

const rankColors = [
    'bg-amber-400 text-amber-950',
    'bg-slate-300 text-slate-800',
    'bg-orange-300 text-orange-950',
];

function barColor(i) {
    return barColors[i % barColors.length];
}
function avatarColor(i) {
    return avatarColors[i % avatarColors.length];
}
function rankColor(i) {
    return rankColors[i] ?? 'bg-gray-100 text-gray-500';
}
function dotColor(status) {
    return statusDot[status] ?? 'bg-gray-400';
}
function initials(name) {
    if (!name) return '?';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() ?? '')
        .join('');
}

// ---- UI only: perhitungan tampilan dari data yang sudah ada ----
function pct(part, total) {
    const p = Number(part ?? 0);
    const t = Number(total ?? 0);
    if (!t) return 0;
    return Math.max(0, Math.min(100, Math.round((p / t) * 100)));
}

const ringRadius = 54;
const ringLength = 2 * Math.PI * ringRadius;
const ready = ref(false);

function ringOffset(value, total) {
    if (!ready.value || !total) return ringLength;
    return ringLength * (1 - Math.min(1, Number(value ?? 0) / Number(total)));
}

onMounted(() => {
    requestAnimationFrame(() => {
        ready.value = true;
    });
});

const today = new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

function statLabel(status) {
    return statusLabel[status] ?? status;
}

function statColor(status) {
    return statusColor[status] ?? 'bg-gray-100 text-gray-500 ring-gray-500/20';
}

function stat(value) {
    return value ?? 0;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';

    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';

    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function topAlternativeName(session) {
    return session?.result?.details?.[0]?.alternative?.name ?? null;
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- ================================================= -->
            <!-- ADMIN                                              -->
            <!-- ================================================= -->
            <template v-if="role === 'admin'">

                <!-- HERO: ringkasan + cincin progres tes + 4 angka utama -->
                <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-indigo-800 to-violet-700 p-6 sm:p-8 text-white shadow-xl shadow-indigo-900/20">
                    <div class="pointer-events-none absolute -top-24 -right-16 h-72 w-72 rounded-full bg-teal-400/30 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-28 left-1/3 h-72 w-72 rounded-full bg-rose-400/25 blur-3xl"></div>
                    <div class="pointer-events-none absolute top-10 left-1/2 h-40 w-40 rounded-full bg-amber-300/20 blur-3xl"></div>

                    <div class="relative flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-md">
                            <p class="text-sm text-indigo-200">{{ today }}</p>
                            <h2 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight">Ringkasan platform</h2>
                            <p class="mt-3 text-sm leading-relaxed text-indigo-100/90">
                                Pantau pengguna, tes, dan konsultasi dalam satu pandangan.
                            </p>
                            <div class="mt-5 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium ring-1 ring-white/20">
                                    <span class="h-2 w-2 rounded-full bg-amber-300"></span>
                                    {{ stat(stats.in_progress_test_sessions) }} tes berjalan
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium ring-1 ring-white/20">
                                    <span class="h-2 w-2 rounded-full bg-rose-300"></span>
                                    {{ stat(stats.pending_consultations) }} konsultasi menunggu
                                </span>
                            </div>
                        </div>

                        <!-- Cincin progres tes -->
                        <div class="flex items-center gap-5">
                            <div class="relative h-36 w-36 shrink-0">
                                <svg viewBox="0 0 128 128" class="h-full w-full -rotate-90" role="img"
                                     :aria-label="`${stat(stats.completed_test_sessions)} dari ${stat(stats.total_test_sessions)} tes selesai`">
                                    <circle cx="64" cy="64" :r="ringRadius" fill="none" stroke="currentColor" stroke-width="12" class="text-white/15" />
                                    <circle cx="64" cy="64" :r="ringRadius" fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round"
                                            class="text-amber-300 transition-[stroke-dashoffset] duration-1000 ease-out motion-reduce:transition-none"
                                            :stroke-dasharray="ringLength"
                                            :stroke-dashoffset="ringOffset(stat(stats.completed_test_sessions) + stat(stats.in_progress_test_sessions), stats.total_test_sessions)" />
                                    <circle cx="64" cy="64" :r="ringRadius" fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round"
                                            class="text-teal-300 transition-[stroke-dashoffset] duration-1000 ease-out motion-reduce:transition-none"
                                            :stroke-dasharray="ringLength"
                                            :stroke-dashoffset="ringOffset(stats.completed_test_sessions, stats.total_test_sessions)" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-bold">{{ pct(stats.completed_test_sessions, stats.total_test_sessions) }}%</span>
                                    <span class="text-[11px] text-indigo-200">tes selesai</span>
                                </div>
                            </div>
                            <ul class="space-y-2 text-xs text-indigo-100">
                                <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-teal-300"></span>{{ stat(stats.completed_test_sessions) }} selesai</li>
                                <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>{{ stat(stats.in_progress_test_sessions) }} berjalan</li>
                                <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-white/30"></span>{{ stat(stats.total_test_sessions) }} total</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 4 angka utama -->
                    <div class="relative mt-8 grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-400 text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            </span>
                            <div>
                                <p class="text-2xl font-bold leading-none">{{ stat(stats.total_users) }}</p>
                                <p class="mt-1 text-xs text-indigo-100">Total User</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-400 text-teal-950">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                            </span>
                            <div>
                                <p class="text-2xl font-bold leading-none">{{ stat(stats.total_psychologists) }}</p>
                                <p class="mt-1 text-xs text-indigo-100">Total Psikolog</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-300 text-amber-950">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" /></svg>
                            </span>
                            <div>
                                <p class="text-2xl font-bold leading-none">{{ stat(stats.total_test_sessions) }}</p>
                                <p class="mt-1 text-xs text-indigo-100">Total Tes</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-400 text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                            </span>
                            <div>
                                <p class="text-2xl font-bold leading-none">{{ stat(stats.total_consultations) }}</p>
                                <p class="mt-1 text-xs text-indigo-100">Total Konsultasi</p>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- TIMELINE aktivitas -->
                    <section class="lg:col-span-7 rounded-3xl bg-white p-6 sm:p-7 ring-1 ring-gray-200/70">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </span>
                            <h2 class="text-lg font-semibold text-gray-800">Aktivitas tes terbaru</h2>
                        </div>

                        <ol v-if="recentSessions.length > 0" class="relative mt-6 ml-4 border-l-2 border-gray-100 space-y-6">
                            <li v-for="(session, i) in recentSessions" :key="session.id" class="relative pl-8">
                                <span class="absolute -left-[9px] top-4 h-4 w-4 rounded-full ring-4 ring-white" :class="dotColor(session.status)"></span>
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-gray-50 p-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-bold" :class="avatarColor(i)">
                                            {{ initials(session.user?.name) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-800">{{ session.user?.name ?? 'User tidak diketahui' }}</p>
                                            <p class="text-xs text-gray-400 capitalize">{{ session.life_phase }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset" :class="statColor(session.status)">
                                            {{ statLabel(session.status) }}
                                        </span>
                                        <span class="text-xs text-gray-400">{{ formatDate(session.started_at) }}</span>
                                    </div>
                                </div>
                            </li>
                        </ol>

                        <div v-else class="mt-6 flex flex-col items-center rounded-2xl border border-dashed border-gray-200 py-12 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-400">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            </span>
                            <p class="mt-3 text-sm text-gray-500">Belum ada aktivitas tes.</p>
                        </div>
                    </section>

                    <!-- Kolom kanan -->
                    <div class="lg:col-span-5 space-y-6">

                        <!-- Kondisi platform: meter -->
                        <section class="rounded-3xl bg-white p-6 sm:p-7 ring-1 ring-gray-200/70">
                            <h2 class="text-lg font-semibold text-gray-800">Kondisi platform</h2>
                            <div class="mt-5 space-y-5">
                                <div>
                                    <div class="flex items-baseline justify-between text-sm">
                                        <span class="font-medium text-gray-700">User aktif</span>
                                        <span class="text-xs text-gray-500">{{ stat(stats.active_users) }} dari {{ stat(stats.total_users) }}</span>
                                    </div>
                                    <div class="mt-2 h-2.5 rounded-full bg-indigo-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-indigo-500" :style="{ width: pct(stats.active_users, stats.total_users) + '%' }"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-baseline justify-between text-sm">
                                        <span class="font-medium text-gray-700">Psikolog terverifikasi</span>
                                        <span class="text-xs text-gray-500">{{ stat(stats.verified_psychologists) }} dari {{ stat(stats.total_psychologists) }}</span>
                                    </div>
                                    <div class="mt-2 h-2.5 rounded-full bg-teal-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-teal-500" :style="{ width: pct(stats.verified_psychologists, stats.total_psychologists) + '%' }"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-baseline justify-between text-sm">
                                        <span class="font-medium text-gray-700">Konsultasi menunggu</span>
                                        <span class="text-xs text-gray-500">{{ stat(stats.pending_consultations) }} dari {{ stat(stats.total_consultations) }}</span>
                                    </div>
                                    <div class="mt-2 h-2.5 rounded-full bg-rose-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-rose-400" :style="{ width: pct(stats.pending_consultations, stats.total_consultations) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Peringkat bidang -->
                        <section class="rounded-3xl bg-white p-6 sm:p-7 ring-1 ring-gray-200/70">
                            <h2 class="text-lg font-semibold text-gray-800">Bidang paling sering direkomendasikan</h2>

                            <ol v-if="topAlternatives.length > 0" class="mt-5 space-y-4">
                                <li v-for="(item, i) in topAlternatives" :key="item.name" class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold" :class="rankColor(i)">{{ i + 1 }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between text-sm mb-1.5">
                                            <span class="truncate font-medium text-gray-700">{{ item.name }}</span>
                                            <span class="ml-2 text-xs font-semibold text-gray-500">{{ item.total }}x</span>
                                        </div>
                                        <div
                                            class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden"
                                            role="progressbar"
                                            :aria-valuenow="item.percent"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            :aria-label="`${item.name}: ${item.total} kali direkomendasikan`"
                                        >
                                            <div class="h-full rounded-full" :class="barColor(i)" :style="{ width: item.percent + '%' }"></div>
                                        </div>
                                    </div>
                                </li>
                            </ol>

                            <div v-else class="mt-5 flex flex-col items-center rounded-2xl border border-dashed border-gray-200 py-10 text-center">
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-indigo-400">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                                </span>
                                <p class="mt-3 text-sm text-gray-500">Belum ada data hasil tes.</p>
                            </div>
                        </section>
                    </div>
                </div>
            </template>

            <!-- ================================================= -->
            <!-- PSIKOLOG                                           -->
            <!-- ================================================= -->
            <template v-else-if="role === 'psikolog'">
                <div v-if="profileMissing" class="rounded-3xl border border-amber-200 bg-amber-50 p-8 text-center text-sm text-amber-800">
                    Akun ini belum punya profil psikolog. Hubungi admin.
                </div>

                <template v-else>
                    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-900 via-teal-700 to-sky-600 p-6 sm:p-8 text-white shadow-xl shadow-teal-900/20">
                        <div class="pointer-events-none absolute -top-20 -right-10 h-64 w-64 rounded-full bg-amber-300/30 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-indigo-400/30 blur-3xl"></div>
                        <div class="relative">
                            <p class="text-sm text-teal-100">{{ today }}</p>
                            <h2 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight">Ruang konsultasi</h2>
                            <p class="mt-3 max-w-md text-sm text-teal-50/90">Lihat siapa yang menunggu balasan dan jadwal yang akan datang.</p>
                        </div>
                        <div class="relative mt-8 grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                                <span class="inline-flex items-center gap-2 text-xs text-teal-50"><span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>Menunggu respon</span>
                                <p class="mt-2 text-4xl font-bold">{{ stat(stats.pending_consultations) }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                                <span class="inline-flex items-center gap-2 text-xs text-teal-50"><span class="h-2.5 w-2.5 rounded-full bg-sky-300"></span>Terjadwal</span>
                                <p class="mt-2 text-4xl font-bold">{{ stat(stats.scheduled_consultations) }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                                <span class="inline-flex items-center gap-2 text-xs text-teal-50"><span class="h-2.5 w-2.5 rounded-full bg-emerald-300"></span>Selesai ditangani</span>
                                <p class="mt-2 text-4xl font-bold">{{ stat(stats.completed_consultations) }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl bg-white p-6 sm:p-7 ring-1 ring-gray-200/70">
                        <h2 class="text-lg font-semibold text-gray-800">Konsultasi terbaru</h2>

                        <ol v-if="recentConsultations.length > 0" class="relative mt-6 ml-4 border-l-2 border-gray-100 space-y-5">
                            <li v-for="(c, i) in recentConsultations" :key="c.id" class="relative pl-8">
                                <span class="absolute -left-[9px] top-5 h-4 w-4 rounded-full ring-4 ring-white" :class="dotColor(c.status)"></span>
                                <Link
                                    :href="route('psikolog.consultations.show', c.id)"
                                    class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50 p-4 transition hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-bold" :class="avatarColor(i)">{{ initials(c.user?.name) }}</span>
                                        <span class="truncate text-sm font-semibold text-gray-800">{{ c.user?.name ?? 'User tidak diketahui' }}</span>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset" :class="statColor(c.status)">{{ statLabel(c.status) }}</span>
                                </Link>
                            </li>
                        </ol>

                        <div v-else class="mt-6 flex flex-col items-center rounded-2xl border border-dashed border-gray-200 py-12 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 text-teal-400">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
                            </span>
                            <p class="mt-3 text-sm text-gray-500">Belum ada konsultasi masuk.</p>
                        </div>
                    </section>
                </template>
            </template>

            <!-- ================================================= -->
            <!-- USER                                               -->
            <!-- ================================================= -->
            <template v-else>
                <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-violet-600 to-rose-500 p-6 sm:p-8 text-white shadow-xl shadow-violet-900/20">
                    <div class="pointer-events-none absolute -top-20 -right-10 h-64 w-64 rounded-full bg-amber-300/40 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-24 left-1/4 h-64 w-64 rounded-full bg-teal-300/30 blur-3xl"></div>

                    <div class="relative grid gap-8 lg:grid-cols-2 lg:items-center">
                        <div>
                            <p class="text-sm text-indigo-100">{{ today }}</p>
                            <h2 class="mt-2 text-3xl sm:text-4xl font-bold tracking-tight">Perjalanan kariermu</h2>
                            <div class="mt-6 grid grid-cols-3 gap-3">
                                <div class="rounded-2xl bg-white/15 p-4 ring-1 ring-white/20 backdrop-blur">
                                    <p class="text-3xl font-bold">{{ stat(stats.total_tests) }}</p>
                                    <p class="mt-1 text-xs text-indigo-50">Total Tes</p>
                                </div>
                                <div class="rounded-2xl bg-white/15 p-4 ring-1 ring-white/20 backdrop-blur">
                                    <p class="text-3xl font-bold">{{ stat(stats.completed_tests) }}</p>
                                    <p class="mt-1 text-xs text-indigo-50">Tes Selesai</p>
                                </div>
                                <div class="rounded-2xl bg-white/15 p-4 ring-1 ring-white/20 backdrop-blur">
                                    <p class="text-3xl font-bold">{{ stat(stats.total_consultations) }}</p>
                                    <p class="mt-1 text-xs text-indigo-50">Konsultasi</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="latestSession" class="rounded-2xl bg-white p-6 text-gray-800 shadow-lg">
                            <p class="text-xs font-medium text-indigo-600">Tes terakhirmu</p>
                            <p class="mt-1 text-2xl font-bold capitalize">{{ latestSession.life_phase }}</p>
                            <p v-if="topAlternativeName(latestSession)" class="mt-3 text-sm text-gray-500">
                                Rekomendasi bidang
                                <span class="mt-1 block text-lg font-semibold text-violet-700">{{ topAlternativeName(latestSession) }}</span>
                            </p>
                            <Link
                                :href="route('tests.show', latestSession.id)"
                                class="mt-5 inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2"
                            >
                                Lihat detail →
                            </Link>
                        </div>

                        <div v-else class="rounded-2xl bg-white p-6 text-center text-gray-800 shadow-lg">
                            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-violet-50 text-violet-500">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>
                            </span>
                            <p class="mt-3 text-sm text-gray-500">Kamu belum pernah mengisi tes.</p>
                            <Link
                                :href="route('tests.create')"
                                class="mt-4 inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2"
                            >
                                Mulai tes pertamamu →
                            </Link>
                        </div>
                    </div>
                </section>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

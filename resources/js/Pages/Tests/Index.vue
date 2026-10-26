<script setup>
import { computed } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    sessions: { type: Array, required: true },

    lifePhaseOptions: { type: Object, default: () => ({}) },
});

const statusLabel = {
    in_progress: 'Sedang berjalan',
    completed: 'Selesai',
};

const statusColor = {
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    in_progress: 'bg-amber-50 text-amber-700 ring-amber-600/20',
};

function topAlternativeName(session) {
    const top = session.result?.top_detail ?? session.result?.topDetail;
    return top?.alternative?.name ?? null;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';

    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';

    return date.toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
}


const completedTotal = computed(() => props.sessions.filter((s) => s.status === 'completed').length);
const inProgressTotal = computed(() => props.sessions.filter((s) => s.status === 'in_progress').length);

function accentBar(status) {
    if (status === 'completed') return 'bg-emerald-400';
    if (status === 'in_progress') return 'bg-amber-400';
    return 'bg-gray-300';
}

function phaseInitial(session) {
    const label = props.lifePhaseOptions[session.life_phase] ?? session.life_phase ?? '?';
    return String(label).trim().charAt(0).toUpperCase();
}

const tileColors = [
    'bg-indigo-100 text-indigo-700',
    'bg-teal-100 text-teal-700',
    'bg-rose-100 text-rose-700',
    'bg-sky-100 text-sky-700',
    'bg-violet-100 text-violet-700',
    'bg-amber-100 text-amber-700',
];

function tileColor(index) {
    return tileColors[index % tileColors.length];
}
</script>

<template>
    <Head title="Riwayat Tes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-gray-800">Riwayat Tes</h1>
                <Link
                    :href="route('tests.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tes Baru
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

            <!-- Kosong -->
            <div
                v-if="sessions.length === 0"
                class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-violet-600 to-rose-500 px-6 py-14 text-center text-white shadow-xl shadow-violet-900/20"
            >
                <div class="pointer-events-none absolute -top-16 -right-10 h-56 w-56 rounded-full bg-amber-300/40 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-1/4 h-56 w-56 rounded-full bg-teal-300/30 blur-3xl"></div>
                <div class="relative">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30 backdrop-blur">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" /></svg>
                    </span>
                    <p class="mt-4 text-lg font-semibold">Kamu belum pernah mengisi tes.</p>
                    <Link
                        :href="route('tests.create')"
                        class="mt-5 inline-flex items-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700 shadow-md transition hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-600"
                    >
                        Mulai tes pertamamu →
                    </Link>
                </div>
            </div>

            <template v-else>
                <!-- Ringkasan -->
                <div class="grid grid-cols-3 gap-3 sm:gap-4">
                    <div class="rounded-2xl bg-indigo-50 p-4 ring-1 ring-indigo-100">
                        <p class="text-2xl font-bold text-indigo-950 sm:text-3xl">{{ sessions.length }}</p>
                        <p class="mt-0.5 text-xs font-medium text-indigo-700 sm:text-sm">Total tes</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                        <p class="text-2xl font-bold text-emerald-950 sm:text-3xl">{{ completedTotal }}</p>
                        <p class="mt-0.5 text-xs font-medium text-emerald-700 sm:text-sm">Selesai</p>
                    </div>
                    <div class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-100">
                        <p class="text-2xl font-bold text-amber-950 sm:text-3xl">{{ inProgressTotal }}</p>
                        <p class="mt-0.5 text-xs font-medium text-amber-700 sm:text-sm">Sedang berjalan</p>
                    </div>
                </div>

                <!-- Daftar tes -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="(session, index) in sessions"
                        :key="session.id"
                        :href="route('tests.show', session.id)"
                        class="group relative flex flex-col overflow-hidden rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200/80 transition duration-200 hover:-translate-y-0.5 hover:shadow-md hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 motion-reduce:transition-none motion-reduce:hover:translate-y-0"
                    >
                        <span class="absolute inset-x-0 top-0 h-1" :class="accentBar(session.status)"></span>

                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-base font-bold" :class="tileColor(index)">
                                    {{ phaseInitial(session) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">
                                        {{ lifePhaseOptions[session.life_phase] ?? session.life_phase }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ formatDate(session.started_at) }}</p>
                                </div>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset"
                                :class="statusColor[session.status] ?? 'bg-gray-100 text-gray-500 ring-gray-500/20'"
                            >
                                {{ statusLabel[session.status] ?? session.status }}
                            </span>
                        </div>

                        <div class="mt-4 flex-1">
                            <div v-if="topAlternativeName(session)" class="rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-50 p-4 ring-1 ring-indigo-100">
                                <p class="text-[11px] font-medium text-indigo-500">Rekomendasi utama</p>
                                <p class="mt-0.5 text-base font-bold text-indigo-900">{{ topAlternativeName(session) }}</p>
                            </div>

                            <div v-else-if="session.status === 'in_progress'" class="rounded-2xl bg-amber-50 p-4 text-xs font-medium text-amber-800 ring-1 ring-amber-100">
                                Klik untuk melanjutkan tes
                                <span class="inline-block transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0">→</span>
                            </div>

                            <div v-else-if="session.status === 'completed'" class="rounded-2xl bg-amber-50 p-4 text-xs font-medium text-amber-800 ring-1 ring-amber-100">
                                Hasil belum tersedia. Buka untuk menghitung ulang
                                <span class="inline-block transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0">→</span>
                            </div>
                        </div>
                    </Link>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

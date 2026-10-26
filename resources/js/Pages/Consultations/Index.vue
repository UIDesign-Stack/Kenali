<script setup>
import { computed, ref } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    consultations: { type: Array, required: true },
});

const statusLabel = {
    pending: 'Menunggu respon',
    scheduled: 'Terjadwal',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

const statusColor = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    scheduled: 'bg-sky-50 text-sky-700 ring-sky-600/20',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    cancelled: 'bg-gray-100 text-gray-500 ring-gray-500/20',
};

const typeLabel = {
    chat: 'Chat',
    tatap_muka: 'Tatap Muka Langsung',
};

const activeFilter = ref('all');

const filters = [
    { key: 'all', label: 'Semua', on: 'bg-indigo-600 text-white shadow-md shadow-indigo-500/30', count: 'bg-white/25 text-white' },
    { key: 'pending', label: 'Menunggu', on: 'bg-amber-400 text-amber-950 shadow-md shadow-amber-400/40', count: 'bg-white/40 text-amber-950' },
    { key: 'scheduled', label: 'Terjadwal', on: 'bg-sky-500 text-white shadow-md shadow-sky-500/30', count: 'bg-white/25 text-white' },
    { key: 'completed', label: 'Selesai', on: 'bg-emerald-500 text-white shadow-md shadow-emerald-500/30', count: 'bg-white/25 text-white' },
    { key: 'cancelled', label: 'Dibatalkan', on: 'bg-gray-500 text-white shadow-md shadow-gray-500/30', count: 'bg-white/25 text-white' },
];

function countFor(key) {
    return key === 'all'
        ? props.consultations.length
        : props.consultations.filter((c) => c.status === key).length;
}

const visibleConsultations = computed(() =>
    activeFilter.value === 'all'
        ? props.consultations
        : props.consultations.filter((c) => c.status === activeFilter.value)
);

const avatarColors = [
    'bg-indigo-100 text-indigo-700',
    'bg-teal-100 text-teal-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-sky-100 text-sky-700',
    'bg-violet-100 text-violet-700',
];

function avatarColor(index) {
    return avatarColors[index % avatarColors.length];
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
</script>

<template>
    <Head title="Konsultasi Saya" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-gray-800">Konsultasi Saya</h1>
                <Link
                    :href="route('consultations.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Ajukan Konsultasi
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

            <!-- Kosong total -->
            <div
                v-if="consultations.length === 0"
                class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-700 via-teal-600 to-sky-600 px-6 py-14 text-center text-white shadow-xl shadow-teal-900/20"
            >
                <div class="pointer-events-none absolute -top-16 -right-10 h-56 w-56 rounded-full bg-amber-300/40 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-1/4 h-56 w-56 rounded-full bg-indigo-400/30 blur-3xl"></div>
                <div class="relative">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30 backdrop-blur">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                    </span>
                    <p class="mt-4 text-lg font-semibold">Belum ada konsultasi.</p>
                    <Link
                        :href="route('consultations.create')"
                        class="mt-5 inline-flex items-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-teal-700 shadow-md transition hover:bg-teal-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-teal-600"
                    >
                        Ajukan konsultasi pertamamu →
                    </Link>
                </div>
            </div>

            <template v-else>
                <!-- Filter status -->
                <div class="flex flex-wrap gap-2" role="group" aria-label="Filter status konsultasi">
                    <button
                        v-for="f in filters"
                        :key="f.key"
                        type="button"
                        @click="activeFilter = f.key"
                        :aria-pressed="activeFilter === f.key"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2"
                        :class="activeFilter === f.key ? f.on : 'bg-white text-gray-600 ring-1 ring-gray-200 hover:bg-gray-50'"
                    >
                        {{ f.label }}
                        <span
                            class="rounded-full px-2 py-0.5 text-[11px] font-bold"
                            :class="activeFilter === f.key ? f.count : 'bg-gray-100 text-gray-500'"
                        >
                            {{ countFor(f.key) }}
                        </span>
                    </button>
                </div>

                <!-- Daftar -->
                <div v-if="visibleConsultations.length" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="(c, index) in visibleConsultations"
                        :key="c.id"
                        :href="route('consultations.show', c.id)"
                        class="group flex flex-col rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200/80 transition duration-200 hover:-translate-y-0.5 hover:shadow-md hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 motion-reduce:transition-none motion-reduce:hover:translate-y-0"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="avatarColor(index)">
                                    {{ initials(c.psychologist_profile.user.name) }}
                                </span>
                                <span class="truncate text-sm font-semibold text-gray-900">{{ c.psychologist_profile.user.name }}</span>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset"
                                :class="statusColor[c.status] ?? 'bg-gray-100 text-gray-500 ring-gray-500/20'"
                            >
                                {{ statusLabel[c.status] ?? c.status }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center gap-2 text-xs text-gray-500">
                            <svg v-if="c.type === 'chat'" class="h-4 w-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                            <svg v-else class="h-4 w-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                            {{ typeLabel[c.type] ?? c.type }}
                        </div>

                        <!-- Ulasan (hanya konsultasi selesai) -->
                        <div v-if="c.status === 'completed'" class="mt-4 border-t border-gray-100 pt-4">
                            <span
                                v-if="c.review"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200"
                            >
                                <span class="text-amber-500">★</span>
                                {{ c.review.rating }} · Sudah diulas
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200"
                            >
                                Beri ulasan
                                <span class="transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0">→</span>
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Kosong untuk filter ini -->
                <div
                    v-else
                    class="rounded-3xl border border-dashed border-gray-300 bg-white py-12 text-center text-sm text-gray-400"
                >
                    Tidak ada konsultasi dengan status ini.
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

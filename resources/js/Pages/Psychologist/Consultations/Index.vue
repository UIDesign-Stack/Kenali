<script setup>
import { computed, ref } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    consultations: { type: Array, required: true },
    profile: { type: Object, default: null },
});

const statusLabel = {
    pending: 'Menunggu respon Anda',
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

function formatDateTime(dateStr) {
    return new Date(dateStr).toLocaleString('id-ID', {
        weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

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
    <Head title="Konsultasi Masuk" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Konsultasi Masuk</h1>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

            <!-- Ulasan tentang saya -->
            <Link
                v-if="profile"
                :href="route('psychologists.reviews', profile.id)"
                class="group relative flex items-center justify-between gap-4 overflow-hidden rounded-3xl bg-gradient-to-br from-teal-800 via-teal-600 to-sky-600 px-6 py-5 text-white shadow-lg shadow-teal-900/20 transition hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 focus-visible:ring-offset-2"
            >
                <div class="pointer-events-none absolute -top-12 -right-8 h-40 w-40 rounded-full bg-amber-300/30 blur-3xl"></div>

                <div class="relative">
                    <p class="text-base font-semibold">Ulasan tentang saya</p>
                    <p class="mt-0.5 text-sm text-teal-50">{{ profile.rating_count }} ulasan</p>
                </div>

                <div class="relative flex items-center gap-3">
                    <span class="flex items-center gap-1.5 rounded-2xl bg-white/15 px-4 py-2 text-2xl font-bold ring-1 ring-white/25 backdrop-blur">
                        <span class="text-amber-300">★</span>
                        {{ profile.rating_count > 0 ? Number(profile.rating_avg).toFixed(1) : '–' }}
                    </span>
                    <span class="text-teal-50 transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0">→</span>
                </div>
            </Link>

            <!-- Filter status -->
            <div v-if="consultations.length" class="flex flex-wrap gap-2" role="group" aria-label="Filter status konsultasi">
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

            <!-- Kosong total -->
            <div
                v-if="consultations.length === 0"
                class="flex flex-col items-center rounded-3xl border border-dashed border-gray-300 bg-white py-16 text-center"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 text-teal-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
                </span>
                <p class="mt-3 text-sm text-gray-500">Belum ada permintaan konsultasi.</p>
            </div>

            <!-- Daftar -->
            <template v-else>
                <div v-if="visibleConsultations.length" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="(c, index) in visibleConsultations"
                        :key="c.id"
                        :href="route('psikolog.consultations.show', c.id)"
                        class="group flex flex-col rounded-3xl bg-white p-5 shadow-sm ring-1 transition duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 motion-reduce:transition-none motion-reduce:hover:translate-y-0"
                        :class="c.status === 'pending' ? 'ring-amber-300' : 'ring-gray-200/80 hover:ring-indigo-200'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="avatarColor(index)">
                                    {{ initials(c.user.name) }}
                                </span>
                                <span class="truncate text-sm font-semibold text-gray-900">{{ c.user.name }}</span>
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

                        <div
                            v-if="c.status === 'scheduled' && c.scheduled_at"
                            class="mt-3 flex items-center gap-2 rounded-xl bg-sky-50 px-3 py-2 text-xs font-medium text-sky-800"
                        >
                            <svg class="h-4 w-4 shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                            Jadwal: {{ formatDateTime(c.scheduled_at) }}
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

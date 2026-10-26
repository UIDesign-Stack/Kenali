<script setup>
import { ref, computed } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    psychologistProfile: { type: Object, required: true },
    consultations: { type: Array, required: true },
});

const MAX_REASON_LENGTH = 1000;

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

const cancellingId = ref(null);

function canForceCancel(consultation) {
    return ['pending', 'scheduled'].includes(consultation.status);
}

function forceCancel(consultation) {
    if (cancellingId.value !== null) return;

    const reason = prompt(
        'PERINGATAN: Ini akan membatalkan konsultasi ini atas nama admin, bukan psikolog.\n' +
        'Gunakan hanya untuk kondisi darurat (psikolog tidak bisa login sendiri).\n\n' +
        'Alasan ini akan terlihat oleh user dan psikolog yang bersangkutan.\n\n' +
        'Masukkan alasan pembatalan:'
    );
    if (!reason || !reason.trim()) return;

    const trimmed = reason.trim();

    if (trimmed.length > MAX_REASON_LENGTH) {
        alert(`Alasan terlalu panjang (${trimmed.length} karakter). Maksimal ${MAX_REASON_LENGTH} karakter.`);
        return;
    }

    cancellingId.value = consultation.id;

    router.patch(route('admin.consultations.force-cancel', consultation.id), {
        cancelled_reason: trimmed,
    }, {
        preserveScroll: true,
        onFinish: () => (cancellingId.value = null),
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
    <Head :title="`Konsultasi — ${psychologistProfile.user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('admin.psychologists.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← Psikolog
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Konsultasi
                    <span class="ml-1 inline-flex items-center rounded-lg bg-gradient-to-r from-indigo-100 to-violet-100 px-2.5 py-0.5 text-indigo-700">{{ psychologistProfile.user.name }}</span>
                </h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

            <!-- Peringatan darurat -->
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                <p>
                    Gunakan tombol "Batalkan Paksa" hanya untuk kondisi darurat, misalnya
                    psikolog tidak bisa login sendiri (sakit mendadak, dll). Alasan wajib
                    diisi dan akan tercatat di riwayat konsultasi.
                </p>
            </div>

            <!-- Notifikasi -->
            <div
                v-if="$page.props.flash?.success"
                class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200"
                role="status"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </span>
                {{ $page.props.flash.success }}
            </div>

            <div
                v-if="$page.props.errors?.consultation || $page.props.errors?.cancelled_reason"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                role="alert"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ $page.props.errors.consultation ?? $page.props.errors.cancelled_reason }}</span>
            </div>

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
                class="flex flex-col items-center rounded-3xl border border-dashed border-gray-300 bg-white py-14 text-center"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 text-teal-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" /></svg>
                </span>
                <p class="mt-3 text-sm text-gray-500">Psikolog ini belum punya konsultasi.</p>
            </div>

            <template v-else>
                <div v-if="visibleConsultations.length" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="(c, index) in visibleConsultations"
                        :key="c.id"
                        class="flex flex-col rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200/80"
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
                            v-if="c.status === 'cancelled' && c.cancelled_reason"
                            class="mt-3 rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-500"
                        >
                            <span class="font-semibold text-gray-600">Alasan:</span> {{ c.cancelled_reason }}
                        </div>

                        <div v-if="canForceCancel(c)" class="mt-4 border-t border-gray-100 pt-4">
                            <button
                                type="button"
                                @click="forceCancel(c)"
                                :disabled="cancellingId !== null"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-600 ring-1 ring-rose-200 transition hover:bg-rose-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                                {{ cancellingId === c.id ? 'Membatalkan…' : 'Batalkan Paksa (Darurat)' }}
                            </button>
                        </div>
                    </article>
                </div>

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

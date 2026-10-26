<script setup>
import { ref, computed } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    psychologists: { type: Array, required: true },
});

const processingId = ref(null);
const processingAction = ref(null);

function isBusy(psych) {
    return processingId.value === psych.id;
}

function isProcessing(psych, action) {
    return isBusy(psych) && processingAction.value === action;
}

function finishProcessing() {
    processingId.value = null;
    processingAction.value = null;
}

function run(psych, action, url) {
    processingId.value = psych.id;
    processingAction.value = action;

    router.patch(url, {}, {
        preserveScroll: true,
        onFinish: finishProcessing,
    });
}

function toggleVerified(psych) {
    if (isBusy(psych)) return;

    const message = psych.is_verified
        ? `Batalkan verifikasi ${psych.user.name}?`
        : `Verifikasi ${psych.user.name}? Pastikan nomor lisensi sudah Anda periksa. Psikolog akan menerima notifikasi.`;

    if (!confirm(message)) return;

    run(psych, 'verified', route('admin.psychologists.toggle-verified', psych.id));
}

function toggleAvailable(psych) {
    if (isBusy(psych)) return;

    if (
        psych.is_available &&
        !confirm(`Nonaktifkan ketersediaan ${psych.user.name}? User tidak akan bisa membuat konsultasi baru dengannya.`)
    ) {
        return;
    }

    run(psych, 'available', route('admin.psychologists.toggle-available', psych.id));
}

// ---------------------------------------------------------------
// UI only: ringkasan, avatar, dan warna
// ---------------------------------------------------------------
const verifiedTotal = computed(() => props.psychologists.filter((p) => p.is_verified).length);
const availableTotal = computed(() => props.psychologists.filter((p) => p.is_available).length);
const waitingTotal = computed(() => props.psychologists.length - verifiedTotal.value);

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

function availableTitle(psych) {
    if (isProcessing(psych, 'available')) return 'Memproses…';
    return psych.is_available ? 'Nonaktifkan ketersediaan' : 'Aktifkan ketersediaan';
}
</script>

<template>
    <Head title="Manajemen Psikolog" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-gray-800">Manajemen Psikolog</h1>
                <Link
                    :href="route('admin.users.create-staff')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Psikolog Baru
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

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
                v-if="$page.props.errors?.psychologist"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                role="alert"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ $page.props.errors.psychologist }}</span>
            </div>

            <!-- Ringkasan -->
            <div v-if="psychologists.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                <div class="rounded-2xl bg-indigo-50 p-4 ring-1 ring-indigo-100">
                    <p class="text-2xl font-bold text-indigo-950 sm:text-3xl">{{ psychologists.length }}</p>
                    <p class="mt-0.5 text-xs font-medium text-indigo-700 sm:text-sm">Total psikolog</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 p-4 ring-1 ring-emerald-100">
                    <p class="text-2xl font-bold text-emerald-950 sm:text-3xl">{{ verifiedTotal }}</p>
                    <p class="mt-0.5 text-xs font-medium text-emerald-700 sm:text-sm">Terverifikasi</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-100">
                    <p class="text-2xl font-bold text-amber-950 sm:text-3xl">{{ waitingTotal }}</p>
                    <p class="mt-0.5 text-xs font-medium text-amber-700 sm:text-sm">Belum diverifikasi</p>
                </div>
                <div class="rounded-2xl bg-teal-50 p-4 ring-1 ring-teal-100">
                    <p class="text-2xl font-bold text-teal-950 sm:text-3xl">{{ availableTotal }}</p>
                    <p class="mt-0.5 text-xs font-medium text-teal-700 sm:text-sm">Tersedia</p>
                </div>
            </div>

            <!-- Daftar psikolog -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <article
                    v-for="(psych, index) in psychologists"
                    :key="psych.id"
                    class="flex flex-col rounded-3xl bg-white p-5 shadow-sm ring-1 transition sm:p-6"
                    :class="psych.is_verified ? 'ring-gray-200/80' : 'ring-amber-300'"
                >
                    <!-- Identitas -->
                    <div class="flex items-start gap-3">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="avatarColor(index)">
                            {{ initials(psych.user.name) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-base font-semibold text-gray-900">{{ psych.user.name }}</p>
                            <p class="truncate text-xs text-gray-400">{{ psych.user.email }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset"
                                    :class="psych.is_verified
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                                        : 'bg-amber-50 text-amber-700 ring-amber-600/20'"
                                >
                                    {{ psych.is_verified ? 'Terverifikasi' : 'Belum diverifikasi' }}
                                </span>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset"
                                    :class="psych.is_available
                                        ? 'bg-teal-50 text-teal-700 ring-teal-600/20'
                                        : 'bg-rose-50 text-rose-700 ring-rose-600/20'"
                                >
                                    {{ psych.is_available ? 'Tersedia' : 'Tidak tersedia' }}
                                </span>
                                <span
                                    v-if="!psych.user.is_active"
                                    class="rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20"
                                >
                                    Akun nonaktif
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Detail -->
                    <div class="mt-4 rounded-2xl bg-gray-50 p-4">
                        <p class="text-sm font-medium text-gray-700">{{ psych.specialization }}</p>
                        <p class="mt-1 text-xs text-gray-400">No. lisensi: {{ psych.license_number }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                            <span>
                                <span class="font-bold text-gray-800">{{ psych.consultations_count }}</span> konsultasi ditangani
                            </span>
                            <span v-if="psych.active_consultations_count > 0" class="font-medium text-amber-600">
                                {{ psych.active_consultations_count }} konsultasi masih aktif
                            </span>
                        </div>
                    </div>

                    <!-- Aksi -->
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <div class="flex items-center gap-1">
                            <Link
                                :href="route('admin.psychologists.consultations', psych.id)"
                                class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-500 transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            >
                                Lihat Konsultasi
                            </Link>
                            <Link
                                :href="route('admin.psychologists.reviews', psych.id)"
                                class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-gray-500 transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            >
                                Ulasan ({{ psych.rating_count }})
                            </Link>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">Tersedia</span>
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="!!psych.is_available"
                                    :aria-label="availableTitle(psych)"
                                    :title="availableTitle(psych)"
                                    @click="toggleAvailable(psych)"
                                    :disabled="isBusy(psych)"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 motion-reduce:transition-none"
                                    :class="psych.is_available ? 'bg-teal-500' : 'bg-gray-300'"
                                >
                                    <span
                                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition motion-reduce:transition-none"
                                        :class="psych.is_available ? 'translate-x-[22px]' : 'translate-x-0.5'"
                                    ></span>
                                </button>
                            </div>

                            <button
                                type="button"
                                @click="toggleVerified(psych)"
                                :disabled="isBusy(psych)"
                                class="rounded-xl px-4 py-2 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="psych.is_verified
                                    ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    : 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/30 hover:from-indigo-700 hover:to-violet-700'"
                            >
                                {{ isProcessing(psych, 'verified') ? '...' : (psych.is_verified ? 'Batalkan Verifikasi' : 'Verifikasi') }}
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <div
                v-if="psychologists.length === 0"
                class="flex flex-col items-center rounded-3xl border border-dashed border-gray-300 bg-white py-14 text-center"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 text-teal-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                </span>
                <p class="mt-3 text-sm text-gray-500">Belum ada psikolog terdaftar.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

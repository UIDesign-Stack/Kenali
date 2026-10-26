<script setup>
import { ref, computed } from 'vue';
import { router, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    alternatives: { type: Array, required: true },
    lifePhaseOptions: { type: Object, default: () => ({}) },
    totalSubCriteria: { type: Number, default: 0 },
});

const page = usePage();

const processingId = ref(null);
const processingAction = ref(null);

function isBusy(alt) {
    return processingId.value === alt.id;
}

function isProcessing(alt, action) {
    return isBusy(alt) && processingAction.value === action;
}

function finishProcessing() {
    processingId.value = null;
    processingAction.value = null;
}

function toggleActive(alternative) {
    if (isBusy(alternative)) return;

    processingId.value = alternative.id;
    processingAction.value = 'toggle';

    router.patch(route('admin.alternatives.toggle-active', alternative.id), {}, {
        preserveScroll: true,
        onFinish: finishProcessing,
    });
}

function destroyAlternative(alternative) {
    if (isBusy(alternative)) return;
    if (! confirm(`Yakin ingin menghapus "${alternative.name}"?`)) return;

    processingId.value = alternative.id;
    processingAction.value = 'destroy';

    router.delete(route('admin.alternatives.destroy', alternative.id), {
        preserveScroll: true,
        onFinish: finishProcessing,
    });
}

// ---------------------------------------------------------------
// UI only: ringkasan, progres profil, dan warna kartu
// ---------------------------------------------------------------
const activeTotal = computed(() => props.alternatives.filter((a) => a.is_active).length);
const completeTotal = computed(
    () => props.alternatives.filter((a) => a.profiles_count >= props.totalSubCriteria).length
);

function isComplete(alt) {
    return alt.profiles_count >= props.totalSubCriteria;
}

function profilePercent(alt) {
    if (!props.totalSubCriteria) return 0;
    return Math.max(0, Math.min(100, Math.round((alt.profiles_count / props.totalSubCriteria) * 100)));
}

function toggleDisabled(alt) {
    return isBusy(alt) || (!alt.is_active && alt.profiles_count < props.totalSubCriteria);
}

function toggleTitle(alt) {
    if (!alt.is_active && alt.profiles_count < props.totalSubCriteria) return 'Lengkapi profil ideal dulu';
    if (isProcessing(alt, 'toggle')) return 'Memproses…';
    return alt.is_active ? 'Nonaktifkan' : 'Aktifkan';
}

const tiles = [
    'bg-indigo-500 text-white shadow-indigo-500/30',
    'bg-teal-500 text-white shadow-teal-500/30',
    'bg-amber-400 text-amber-950 shadow-amber-400/40',
    'bg-rose-400 text-white shadow-rose-400/40',
    'bg-sky-500 text-white shadow-sky-500/30',
    'bg-violet-500 text-white shadow-violet-500/30',
];

function tile(index) {
    return tiles[index % tiles.length];
}

function initial(name) {
    return (name ?? '?').trim().charAt(0).toUpperCase();
}
</script>

<template>
    <Head title="Alternatif Bidang/Karier" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-gray-800">Alternatif Bidang/Karier</h1>
                <Link
                    :href="route('admin.alternatives.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Alternatif
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- Notifikasi -->
            <div
                v-if="page.props.flash?.success"
                class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200"
                role="status"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </span>
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="page.props.errors?.alternative"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                role="alert"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ page.props.errors.alternative }}</span>
            </div>

            <!-- Ringkasan -->
            <div v-if="alternatives.length" class="grid grid-cols-3 gap-3 sm:gap-4">
                <div class="rounded-2xl bg-indigo-50 p-4 ring-1 ring-indigo-100">
                    <p class="text-2xl font-bold text-indigo-950 sm:text-3xl">{{ alternatives.length }}</p>
                    <p class="mt-0.5 text-xs font-medium text-indigo-700 sm:text-sm">Total alternatif</p>
                </div>
                <div class="rounded-2xl bg-teal-50 p-4 ring-1 ring-teal-100">
                    <p class="text-2xl font-bold text-teal-950 sm:text-3xl">{{ activeTotal }}</p>
                    <p class="mt-0.5 text-xs font-medium text-teal-700 sm:text-sm">Aktif</p>
                </div>
                <div class="rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-100">
                    <p class="text-2xl font-bold text-amber-950 sm:text-3xl">{{ completeTotal }}</p>
                    <p class="mt-0.5 text-xs font-medium text-amber-700 sm:text-sm">Profil ideal lengkap</p>
                </div>
            </div>

            <!-- Daftar alternatif -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="(alt, index) in alternatives"
                    :key="alt.id"
                    class="flex flex-col rounded-3xl p-5 ring-1 transition"
                    :class="alt.is_active ? 'bg-white ring-gray-200/80 shadow-sm' : 'bg-gray-50 ring-gray-200/60'"
                >
                    <div class="flex-1" :class="{ 'opacity-60': !alt.is_active }">
                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-bold shadow-md"
                                :class="tile(index)"
                            >
                                {{ initial(alt.name) }}
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold leading-snug text-gray-900">{{ alt.name }}</h3>
                                <span class="mt-1 inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-medium text-gray-600">
                                    {{ lifePhaseOptions[alt.life_phase] ?? alt.life_phase }}
                                </span>
                            </div>
                        </div>

                        <p v-if="alt.description" class="mt-3 line-clamp-2 text-sm leading-relaxed text-gray-500">
                            {{ alt.description }}
                        </p>

                        <!-- Kelengkapan profil ideal -->
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-gray-600">Profil ideal</span>
                                <span
                                    class="rounded-full px-2 py-0.5 font-semibold"
                                    :class="isComplete(alt) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                >
                                    {{ alt.profiles_count }}/{{ totalSubCriteria }}
                                </span>
                            </div>
                            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                <div
                                    class="h-full rounded-full transition-all duration-500 motion-reduce:transition-none"
                                    :class="isComplete(alt) ? 'bg-emerald-500' : 'bg-amber-400'"
                                    :style="{ width: profilePercent(alt) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Aksi -->
                    <div class="mt-5 flex items-center gap-2 border-t border-gray-100 pt-4">
                        <Link
                            :href="route('admin.alternative-profiles.edit', alt.id)"
                            class="inline-flex flex-1 items-center justify-center rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        >
                            Atur Profil Ideal
                        </Link>

                        <Link
                            :href="route('admin.alternatives.edit', alt.id)"
                            title="Edit"
                            aria-label="Edit alternatif"
                            class="rounded-lg p-2 text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        >
                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        </Link>

                        <button
                            type="button"
                            role="switch"
                            :aria-checked="!!alt.is_active"
                            :aria-label="alt.is_active ? 'Nonaktifkan alternatif' : 'Aktifkan alternatif'"
                            :title="toggleTitle(alt)"
                            @click="toggleActive(alt)"
                            :disabled="toggleDisabled(alt)"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 motion-reduce:transition-none"
                            :class="alt.is_active ? 'bg-teal-500' : 'bg-gray-300'"
                        >
                            <span
                                class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition motion-reduce:transition-none"
                                :class="alt.is_active ? 'translate-x-[22px]' : 'translate-x-0.5'"
                            ></span>
                        </button>

                        <button
                            type="button"
                            @click="destroyAlternative(alt)"
                            :disabled="isBusy(alt)"
                            :title="isProcessing(alt, 'destroy') ? 'Menghapus…' : 'Hapus'"
                            aria-label="Hapus alternatif"
                            class="rounded-lg p-2 text-gray-400 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </div>
                </article>
            </div>

            <!-- Kosong -->
            <div
                v-if="alternatives.length === 0"
                class="flex flex-col items-center rounded-3xl border border-dashed border-gray-300 bg-white py-14 text-center"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-indigo-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122" /></svg>
                </span>
                <p class="mt-3 text-sm text-gray-500">Belum ada alternatif. Klik "Tambah Alternatif" untuk mulai.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
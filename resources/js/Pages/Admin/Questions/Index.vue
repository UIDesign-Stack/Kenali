<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, Head } from '@inertiajs/vue3';

const props = defineProps({
    criteria: { type: Array, required: true },
});

function activeCount(sub) {
    return sub.active_questions_count ?? sub.questions_count;
}

// ---------------------------------------------------------------
// UI only: ringkasan & warna (dihitung dari data yang sudah ada)
// ---------------------------------------------------------------
const totals = computed(() => {
    let questions = 0;
    let active = 0;
    let subs = 0;

    for (const crit of props.criteria) {
        for (const sub of crit.sub_criteria ?? []) {
            subs += 1;
            questions += Number(sub.questions_count ?? 0);
            active += Number(activeCount(sub) ?? 0);
        }
    }

    return { questions, active, subs };
});

function activePercent(sub) {
    const total = Number(sub.questions_count ?? 0);
    if (!total) return 0;
    return Math.max(0, Math.min(100, Math.round((Number(activeCount(sub)) / total) * 100)));
}

const palettes = [
    { tile: 'bg-indigo-500 text-white shadow-indigo-500/30', soft: 'bg-indigo-50 text-indigo-700', ring: 'hover:ring-indigo-300', bar: 'bg-indigo-500' },
    { tile: 'bg-teal-500 text-white shadow-teal-500/30', soft: 'bg-teal-50 text-teal-700', ring: 'hover:ring-teal-300', bar: 'bg-teal-500' },
    { tile: 'bg-amber-400 text-amber-950 shadow-amber-400/40', soft: 'bg-amber-50 text-amber-700', ring: 'hover:ring-amber-300', bar: 'bg-amber-400' },
    { tile: 'bg-rose-400 text-white shadow-rose-400/40', soft: 'bg-rose-50 text-rose-700', ring: 'hover:ring-rose-300', bar: 'bg-rose-400' },
    { tile: 'bg-sky-500 text-white shadow-sky-500/30', soft: 'bg-sky-50 text-sky-700', ring: 'hover:ring-sky-300', bar: 'bg-sky-500' },
    { tile: 'bg-violet-500 text-white shadow-violet-500/30', soft: 'bg-violet-50 text-violet-700', ring: 'hover:ring-violet-300', bar: 'bg-violet-500' },
];

function palette(index) {
    return palettes[index % palettes.length];
}

function initial(name) {
    return (name ?? '?').trim().charAt(0).toUpperCase();
}
</script>

<template>
    <Head title="Bank Soal" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Bank Soal</h1>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- Kosong -->
            <div
                v-if="!criteria.length"
                class="flex flex-col items-center rounded-3xl border border-dashed border-gray-300 bg-white py-16 text-center"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-indigo-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                    </svg>
                </span>
                <p class="mt-3 text-sm text-gray-500">Belum ada kriteria yang tersedia.</p>
            </div>

            <template v-else>
                <!-- Ringkasan -->
                <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-indigo-800 to-violet-700 p-6 text-white shadow-lg shadow-indigo-900/20">
                    <div class="pointer-events-none absolute -top-16 -right-10 h-56 w-56 rounded-full bg-teal-400/30 blur-3xl"></div>
                    <div class="pointer-events-none absolute -bottom-20 left-1/3 h-56 w-56 rounded-full bg-rose-400/25 blur-3xl"></div>

                    <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight">Kelola soal tes</h2>
                            <p class="mt-1 text-sm text-indigo-100">Pilih sub-kriteria untuk menambah atau mengubah soalnya.</p>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-2xl bg-white/10 px-4 py-3 text-center ring-1 ring-white/15 backdrop-blur">
                                <p class="text-2xl font-bold leading-none">{{ criteria.length }}</p>
                                <p class="mt-1 text-[11px] text-indigo-100">Kriteria</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 px-4 py-3 text-center ring-1 ring-white/15 backdrop-blur">
                                <p class="text-2xl font-bold leading-none">{{ totals.questions }}</p>
                                <p class="mt-1 text-[11px] text-indigo-100">Total soal</p>
                            </div>
                            <div class="rounded-2xl bg-white/10 px-4 py-3 text-center ring-1 ring-white/15 backdrop-blur">
                                <p class="text-2xl font-bold leading-none text-teal-200">{{ totals.active }}</p>
                                <p class="mt-1 text-[11px] text-indigo-100">Soal aktif</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Per kriteria -->
                <section
                    v-for="(crit, cIndex) in criteria"
                    :key="crit.id"
                    class="rounded-3xl bg-white p-5 sm:p-6 ring-1 ring-gray-200/80 shadow-sm"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-bold shadow-md"
                            :class="palette(cIndex).tile"
                        >
                            {{ initial(crit.name) }}
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">{{ crit.name }}</h2>
                            <p class="text-xs text-gray-400">{{ crit.sub_criteria.length }} sub-kriteria</p>
                        </div>
                    </div>

                    <div v-if="!crit.sub_criteria.length" class="mt-4 rounded-2xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400">
                        Belum ada sub-kriteria.
                    </div>

                    <div v-else class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="sub in crit.sub_criteria"
                            :key="sub.id"
                            :href="route('admin.questions.manage', sub.id)"
                            class="group rounded-2xl bg-gray-50 p-4 ring-1 ring-gray-200/80 transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 motion-reduce:transition-none motion-reduce:hover:translate-y-0"
                            :class="palette(cIndex).ring"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-sm font-semibold text-gray-800 leading-snug">{{ sub.name }}</span>
                                <span class="shrink-0 text-gray-300 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-gray-500 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0">→</span>
                            </div>

                            <div class="mt-3 flex items-end gap-1.5">
                                <span class="text-3xl font-bold leading-none text-gray-900">{{ sub.questions_count }}</span>
                                <span class="pb-0.5 text-xs text-gray-500">soal</span>
                            </div>

                            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                <div
                                    class="h-full rounded-full"
                                    :class="activeCount(sub) > 0 ? palette(cIndex).bar : 'bg-amber-300'"
                                    :style="{ width: activePercent(sub) + '%' }"
                                ></div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[11px] font-medium"
                                    :class="activeCount(sub) > 0
                                        ? 'bg-teal-100 text-teal-700'
                                        : 'bg-amber-100 text-amber-700'"
                                >
                                    {{ activeCount(sub) }} aktif
                                </span>
                                <span
                                    v-if="activeCount(sub) === 0"
                                    class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-200"
                                >
                                    belum bisa dipakai di tes
                                </span>
                            </div>
                        </Link>
                    </div>
                </section>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const DEFAULT_SCORE = 3;

const props = defineProps({
    alternative: { type: Object, required: true },
    criteria: { type: Array, required: true },
    existingScores: { type: [Object, Array], required: true },
});

const scores = reactive({});
const touched = reactive({});

props.criteria.forEach((crit) => {
    crit.sub_criteria.forEach((sub) => {
        const existing = props.existingScores?.[sub.id];
        const hasValue = existing !== undefined && existing !== null;

        scores[sub.id] = hasValue ? Number(existing) : DEFAULT_SCORE;
        touched[sub.id] = hasValue;
    });
});

const untouchedCount = computed(() => Object.values(touched).filter((value) => !value).length);

const saving = ref(false);
const errorMessage = ref('');

function markTouched(subCriteriaId) {
    touched[subCriteriaId] = true;
}

function submit() {
    if (saving.value) return;

    if (!props.criteria.length) {
        errorMessage.value = 'Tidak ada kriteria untuk disimpan.';
        return;
    }

    if (
        untouchedCount.value > 0 &&
        !confirm(
            `${untouchedCount.value} sub-kriteria masih memakai nilai awal ${DEFAULT_SCORE} yang belum Anda atur. Tetap simpan?`
        )
    ) {
        return;
    }

    saving.value = true;
    errorMessage.value = '';

    const payload = Object.entries(scores).map(([subCriteriaId, idealScore]) => ({
        sub_criteria_id: Number(subCriteriaId),
        ideal_score: idealScore,
    }));

    router.put(route('admin.alternative-profiles.update', props.alternative.id), {
        scores: payload,
    }, {
        onError: (errors) => {
            errorMessage.value = Object.values(errors).flat().join(' ') || 'Gagal menyimpan profil ideal.';
        },
        onFinish: () => (saving.value = false),
    });
}

// ---------------------------------------------------------------
// UI only: progres pengaturan, label skor, dan warna per kriteria
// ---------------------------------------------------------------
const totalCount = computed(() => Object.keys(touched).length);
const setCount = computed(() => totalCount.value - untouchedCount.value);
const progressPercent = computed(() =>
    totalCount.value ? Math.round((setCount.value / totalCount.value) * 100) : 0
);

const scoreWord = {
    1: 'Sangat rendah',
    2: 'Rendah',
    3: 'Sedang',
    4: 'Tinggi',
    5: 'Sangat tinggi',
};

const palettes = [
    { tile: 'bg-indigo-500 text-white shadow-indigo-500/30', badge: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20', accent: 'accent-indigo-600', on: 'text-indigo-600' },
    { tile: 'bg-teal-500 text-white shadow-teal-500/30', badge: 'bg-teal-50 text-teal-700 ring-teal-600/20', accent: 'accent-teal-600', on: 'text-teal-600' },
    { tile: 'bg-amber-400 text-amber-950 shadow-amber-400/40', badge: 'bg-amber-50 text-amber-700 ring-amber-600/20', accent: 'accent-amber-500', on: 'text-amber-600' },
    { tile: 'bg-rose-400 text-white shadow-rose-400/40', badge: 'bg-rose-50 text-rose-700 ring-rose-600/20', accent: 'accent-rose-500', on: 'text-rose-600' },
    { tile: 'bg-sky-500 text-white shadow-sky-500/30', badge: 'bg-sky-50 text-sky-700 ring-sky-600/20', accent: 'accent-sky-600', on: 'text-sky-600' },
    { tile: 'bg-violet-500 text-white shadow-violet-500/30', badge: 'bg-violet-50 text-violet-700 ring-violet-600/20', accent: 'accent-violet-600', on: 'text-violet-600' },
];

function palette(index) {
    return palettes[index % palettes.length];
}

function initial(name) {
    return (name ?? '?').trim().charAt(0).toUpperCase();
}
</script>

<template>
    <Head :title="`Profil Ideal — ${alternative.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('admin.alternatives.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← Alternatif
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Profil Ideal
                    <span class="ml-1 inline-flex items-center rounded-lg bg-gradient-to-r from-indigo-100 to-violet-100 px-2.5 py-0.5 text-indigo-700">{{ alternative.name }}</span>
                </h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- Banner penjelasan + progres -->
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900 via-indigo-700 to-violet-600 px-6 py-5 text-white shadow-lg shadow-indigo-900/20">
                <div class="pointer-events-none absolute -top-16 -right-10 h-48 w-48 rounded-full bg-rose-400/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-10 h-48 w-48 rounded-full bg-teal-300/25 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-2xl text-sm leading-relaxed text-indigo-100">
                        Tentukan skor ideal (1 = sangat rendah, 5 = sangat tinggi) untuk tiap
                        sub-kriteria, seandainya ada orang yang sangat cocok dengan bidang
                        <strong class="font-semibold text-white">{{ alternative.name }}</strong>.
                    </p>
                    <div class="w-full shrink-0 sm:w-52">
                        <div class="flex items-center justify-between text-xs font-medium text-indigo-50">
                            <span>Sudah diatur</span>
                            <span>{{ setCount }} dari {{ totalCount }}</span>
                        </div>
                        <div
                            class="mt-2 h-2 overflow-hidden rounded-full bg-white/20"
                            role="progressbar"
                            :aria-valuenow="progressPercent"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="Progres pengaturan profil ideal"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-teal-300 to-amber-300 transition-all duration-500 motion-reduce:transition-none"
                                :style="{ width: progressPercent + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </section>

            <div
                v-if="errorMessage"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                role="alert"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ errorMessage }}</span>
            </div>

            <form @submit.prevent="submit">
                <!-- Kartu per kriteria -->
                <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-2 xl:grid-cols-3">
                    <section
                        v-for="(crit, cIndex) in criteria"
                        :key="crit.id"
                        class="rounded-3xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm sm:p-6"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-base font-bold shadow-md"
                                :class="palette(cIndex).tile"
                            >
                                {{ initial(crit.name) }}
                            </span>
                            <h2 class="text-base font-semibold text-gray-800">{{ crit.name }}</h2>
                        </div>

                        <div class="mt-5 space-y-6">
                            <div v-for="sub in crit.sub_criteria" :key="sub.id">
                                <div class="flex items-center justify-between gap-2">
                                    <label :for="`sub-${sub.id}`" class="text-sm font-medium text-gray-700">
                                        {{ sub.name }}
                                    </label>
                                    <span
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
                                        :class="touched[sub.id] ? palette(cIndex).badge : 'bg-amber-50 text-amber-700 ring-amber-600/20'"
                                    >
                                        {{ scores[sub.id] }}
                                        <span v-if="!touched[sub.id]" class="text-[10px] font-normal">belum diatur</span>
                                        <span v-else class="text-[10px] font-normal opacity-80">{{ scoreWord[scores[sub.id]] }}</span>
                                    </span>
                                </div>

                                <input
                                    :id="`sub-${sub.id}`"
                                    v-model.number="scores[sub.id]"
                                    type="range"
                                    min="1"
                                    max="5"
                                    step="1"
                                    :aria-valuetext="`${scores[sub.id]} dari 5`"
                                    class="mt-3 w-full cursor-pointer"
                                    :class="touched[sub.id] ? palette(cIndex).accent : 'accent-gray-400'"
                                    @input="markTouched(sub.id)"
                                />

                                <div class="mt-1 flex justify-between px-1 text-[11px]" aria-hidden="true">
                                    <span
                                        v-for="n in 5"
                                        :key="n"
                                        class="w-4 text-center font-medium"
                                        :class="scores[sub.id] === n && touched[sub.id] ? [palette(cIndex).on, 'font-bold'] : 'text-gray-300'"
                                    >{{ n }}</span>
                                </div>
                                <div class="mt-0.5 flex justify-between text-[10px] text-gray-400">
                                    <span>Sangat rendah</span>
                                    <span>Sedang</span>
                                    <span>Sangat tinggi</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Bar aksi menempel di bawah -->
                <div class="sticky bottom-4 z-10 mt-6">
                    <div class="flex flex-col gap-3 rounded-2xl bg-white/90 p-3 pl-5 shadow-lg shadow-gray-900/10 ring-1 ring-gray-200 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm" :class="untouchedCount > 0 ? 'text-amber-700' : 'text-emerald-600'">
                            <template v-if="untouchedCount > 0">
                                <span class="font-semibold">{{ untouchedCount }}</span> sub-kriteria belum diatur dan masih bernilai awal {{ DEFAULT_SCORE }}.
                                Geser slider sesuai profil ideal sebelum menyimpan.
                            </template>
                            <template v-else>
                                <span class="font-medium">Semua sub-kriteria sudah diatur.</span>
                            </template>
                        </p>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                        >
                            <svg v-if="saving" class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                            </svg>
                            {{ saving ? 'Menyimpan…' : 'Simpan Profil Ideal' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
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
</script>

<template>
    <Head :title="`Profil Ideal — ${alternative.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.alternatives.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Alternatif
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Profil Ideal — {{ alternative.name }}
                </h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <p class="text-sm text-gray-500 mb-8">
                Tentukan skor ideal (1 = sangat rendah, 5 = sangat tinggi) untuk tiap
                sub-kriteria, seandainya ada orang yang sangat cocok dengan bidang
                <strong>{{ alternative.name }}</strong>.
            </p>

            <div v-if="errorMessage" class="mb-6 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600" role="alert">
                {{ errorMessage }}
            </div>

            <p
                v-if="untouchedCount > 0"
                class="mb-6 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3 py-2"
            >
                {{ untouchedCount }} sub-kriteria belum diatur dan masih bernilai awal {{ DEFAULT_SCORE }}.
                Geser slider sesuai profil ideal sebelum menyimpan.
            </p>

            <form @submit.prevent="submit" class="space-y-10">
                <div v-for="crit in criteria" :key="crit.id">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">
                        {{ crit.name }}
                    </h2>

                    <div class="space-y-5">
                        <div v-for="sub in crit.sub_criteria" :key="sub.id">
                            <div class="flex items-center justify-between mb-1">
                                <label :for="`sub-${sub.id}`" class="text-sm font-medium text-gray-700">
                                    {{ sub.name }}
                                </label>
                                <span class="text-sm font-semibold text-teal-600">
                                    {{ scores[sub.id] }}
                                    <span v-if="!touched[sub.id]" class="ml-1 text-[10px] font-normal text-amber-600">belum diatur</span>
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
                                class="w-full accent-teal-600"
                                @input="markTouched(sub.id)"
                            />
                            <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                                <span>1 · Sangat rendah</span>
                                <span>3 · Sedang</span>
                                <span>5 · Sangat tinggi</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="saving"
                    class="mt-2 px-5 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    {{ saving ? 'Menyimpan…' : 'Simpan Profil Ideal' }}
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

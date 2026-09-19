<script setup>
import { router, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    testSession: { type: Object, required: true },
    result: { type: Object, default: null },
});

const retrying = ref(false);
const retryError = ref(null);

const hasResult = computed(() => props.result && props.result.details?.length > 0);

function scorePercent(score) {
    const num = Number(score);
    return Number.isFinite(num) ? Math.round(num * 100) : 0;
}

function retryCalculation() {
    retrying.value = true;
    retryError.value = null;

    router.post(route('tests.recalculate', props.testSession.id), {}, {
        onError: (errors) => {
            retryError.value = errors.recalculate ?? errors.message ?? 'Gagal menghitung ulang.';
        },
        onFinish: () => (retrying.value = false),
    });
}
</script>

<template>
    <Head title="Hasil Rekomendasi" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Hasil Rekomendasi</h1>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <div class="text-center mb-8">
                <p class="text-2xl mb-1">{{ hasResult ? '🎉' : '⏳' }}</p>
                <p class="text-sm text-gray-500">Tes untuk fase</p>
                <p class="text-lg font-semibold text-gray-800 capitalize">{{ testSession.life_phase }}</p>
            </div>

            <div v-if="!hasResult" class="p-6 border border-amber-200 bg-amber-50 rounded-lg text-center">
                <p class="text-sm text-amber-700 mb-4">
                    Hasil belum bisa dihitung. Kemungkinan bobot AHP atau data
                    alternatif belum lengkap di sistem saat itu.
                </p>
                <button
                    type="button"
                    @click="retryCalculation"
                    :disabled="retrying"
                    class="px-4 py-2 rounded-md bg-amber-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-amber-700"
                >
                    {{ retrying ? 'Menghitung…' : 'Coba Hitung Ulang' }}
                </button>
                <p v-if="retryError" class="text-xs text-red-600 mt-3" role="alert">{{ retryError }}</p>
            </div>

            <div v-else class="space-y-3">
                <p class="text-sm text-gray-500 mb-2">Bidang yang paling cocok untukmu:</p>

                <div
                    v-for="detail in result.details"
                    :key="detail.id"
                    class="p-4 border rounded-lg"
                    :class="detail.rank === 1 ? 'border-teal-400 bg-teal-50' : 'border-gray-200'"
                >
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full"
                                :class="detail.rank === 1 ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-500'"
                            >
                                {{ detail.rank }}
                            </span>
                            <span class="text-sm font-semibold text-gray-800">
                                {{ detail.alternative?.name ?? 'Data tidak tersedia' }}
                            </span>
                        </div>
                        <span class="text-sm font-medium text-teal-600">{{ scorePercent(detail.score) }}%</span>
                    </div>
                    <p v-if="detail.alternative?.description" class="text-xs text-gray-500 ml-8">
                        {{ detail.alternative.description }}
                    </p>
                    <div
                        class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden mt-2 ml-8"
                        role="progressbar"
                        :aria-valuenow="scorePercent(detail.score)"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="`Skor kecocokan ${detail.alternative?.name ?? ''}`"
                    >
                        <div
                            class="h-full bg-teal-500"
                            :style="{ width: scorePercent(detail.score) + '%' }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
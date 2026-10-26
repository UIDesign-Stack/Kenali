<script setup>
import { router, Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
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
    if (retrying.value) return;

    retrying.value = true;
    retryError.value = null;

    router.post(route('tests.recalculate', props.testSession.id), {}, {
        onError: (errors) => {
            retryError.value = errors.recalculate ?? errors.message ?? 'Gagal menghitung ulang.';
        },
        onFinish: () => (retrying.value = false),
    });
}

let removeInvalid = null;

onMounted(() => {
    removeInvalid = router.on('invalid', (event) => {
        if (event.detail.response.status === 429) {
            event.preventDefault();
            retryError.value = 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.';
            retrying.value = false;
        }
    });
});

onBeforeUnmount(() => removeInvalid?.());

// ---------------------------------------------------------------
// UI only: pemisahan peringkat 1, cincin skor, dan warna
// ---------------------------------------------------------------
const topDetail = computed(() => {
    const details = props.result?.details ?? [];
    return details.find((d) => d.rank === 1) ?? details[0] ?? null;
});

const otherDetails = computed(() =>
    (props.result?.details ?? []).filter((d) => d !== topDetail.value)
);

const ringRadius = 54;
const ringLength = 2 * Math.PI * ringRadius;
const ready = ref(false);

function ringOffset(score) {
    if (!ready.value) return ringLength;
    return ringLength * (1 - Math.min(100, scorePercent(score)) / 100);
}

onMounted(() => {
    requestAnimationFrame(() => {
        ready.value = true;
    });
});

const rankStyles = [
    { badge: 'bg-indigo-500 text-white', bar: 'bg-indigo-500' },
    { badge: 'bg-teal-500 text-white', bar: 'bg-teal-500' },
    { badge: 'bg-amber-400 text-amber-950', bar: 'bg-amber-400' },
    { badge: 'bg-rose-400 text-white', bar: 'bg-rose-400' },
    { badge: 'bg-sky-500 text-white', bar: 'bg-sky-500' },
    { badge: 'bg-violet-500 text-white', bar: 'bg-violet-500' },
];

function rankStyle(index) {
    return rankStyles[index % rankStyles.length];
}
</script>

<template>
    <Head title="Hasil Rekomendasi" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Hasil Rekomendasi</h1>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">
            <!-- Pesan dari server, mis. "Tes selesai..." atau "Hasil berhasil dihitung ulang." -->
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

            <!-- ===== Hasil belum tersedia ===== -->
            <section
                v-if="!hasResult"
                class="mx-auto max-w-xl rounded-3xl border border-amber-200 bg-amber-50 p-8 text-center"
            >
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-400 text-amber-950 shadow-lg shadow-amber-400/40">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </span>
                <p class="mt-4 text-sm text-amber-800">Tes untuk fase</p>
                <p class="text-lg font-semibold capitalize text-gray-900">{{ testSession.life_phase }}</p>
                <p class="mt-4 text-sm leading-relaxed text-amber-800">
                    Hasil rekomendasi belum bisa ditampilkan saat ini. Silakan coba hitung ulang,
                    atau hubungi admin jika masalah berlanjut.
                </p>
                <button
                    type="button"
                    @click="retryCalculation"
                    :disabled="retrying"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-amber-500/30 transition hover:bg-amber-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <svg v-if="retrying" class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                    {{ retrying ? 'Menghitung…' : 'Coba Hitung Ulang' }}
                </button>
                <p v-if="retryError" class="mt-3 text-xs text-rose-600" role="alert">{{ retryError }}</p>
            </section>

            <!-- ===== Hasil tersedia ===== -->
            <template v-else>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

                    <!-- Rekomendasi utama -->
                    <section
                        v-if="topDetail"
                        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900 via-indigo-700 to-violet-600 p-6 text-white shadow-xl shadow-indigo-900/20 sm:p-8 lg:col-span-2"
                    >
                        <div class="pointer-events-none absolute -top-16 -right-10 h-56 w-56 rounded-full bg-teal-400/30 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-20 left-1/3 h-56 w-56 rounded-full bg-rose-400/25 blur-3xl"></div>

                        <div class="relative">
                            <p class="text-sm text-indigo-100">Tes untuk fase</p>
                            <p class="text-lg font-semibold capitalize">{{ testSession.life_phase }}</p>

                            <p class="mt-6 text-sm text-indigo-100">Bidang yang paling cocok untukmu:</p>
                            <p class="mt-1 text-2xl font-bold leading-snug sm:text-3xl">
                                {{ topDetail.alternative?.name ?? 'Data tidak tersedia' }}
                            </p>
                            <p v-if="topDetail.alternative?.description" class="mt-3 text-sm leading-relaxed text-indigo-100">
                                {{ topDetail.alternative.description }}
                            </p>

                            <div class="mt-6 flex items-center gap-5">
                                <div class="relative h-32 w-32 shrink-0">
                                    <svg
                                        viewBox="0 0 128 128"
                                        class="h-full w-full -rotate-90"
                                        role="progressbar"
                                        :aria-valuenow="scorePercent(topDetail.score)"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        :aria-label="`Skor kedekatan ${topDetail.alternative?.name ?? ''}`"
                                    >
                                        <circle cx="64" cy="64" :r="ringRadius" fill="none" stroke="currentColor" stroke-width="12" class="text-white/15" />
                                        <circle
                                            cx="64" cy="64" :r="ringRadius" fill="none" stroke="currentColor" stroke-width="12" stroke-linecap="round"
                                            class="text-amber-300 transition-[stroke-dashoffset] duration-1000 ease-out motion-reduce:transition-none"
                                            :stroke-dasharray="ringLength"
                                            :stroke-dashoffset="ringOffset(topDetail.score)"
                                        />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-3xl font-bold">{{ scorePercent(topDetail.score) }}%</span>
                                        <span class="text-[11px] text-indigo-200">kedekatan</span>
                                    </div>
                                </div>
                                <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold ring-1 ring-white/25">
                                    Peringkat {{ topDetail.rank }}
                                </span>
                            </div>
                        </div>
                    </section>

                    <!-- Bidang lainnya -->
                    <section class="rounded-3xl bg-white p-6 ring-1 ring-gray-200/80 shadow-sm sm:p-8 lg:col-span-3">
                        <h2 class="text-lg font-semibold text-gray-800">Bidang lain yang cocok</h2>

                        <ul v-if="otherDetails.length" class="mt-5 space-y-5">
                            <li v-for="(detail, index) in otherDetails" :key="detail.id">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                            :class="rankStyle(index + 1).badge"
                                        >
                                            {{ detail.rank }}
                                        </span>
                                        <span class="truncate text-sm font-semibold text-gray-800">
                                            {{ detail.alternative?.name ?? 'Data tidak tersedia' }}
                                        </span>
                                    </div>
                                    <span class="shrink-0 text-sm font-bold text-gray-700">{{ scorePercent(detail.score) }}%</span>
                                </div>

                                <p v-if="detail.alternative?.description" class="mt-1.5 pl-11 text-xs leading-relaxed text-gray-500">
                                    {{ detail.alternative.description }}
                                </p>

                                <div
                                    class="ml-11 mt-2 h-2 overflow-hidden rounded-full bg-gray-100"
                                    role="progressbar"
                                    :aria-valuenow="scorePercent(detail.score)"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    :aria-label="`Skor kedekatan ${detail.alternative?.name ?? ''}`"
                                >
                                    <div
                                        class="h-full rounded-full"
                                        :class="rankStyle(index + 1).bar"
                                        :style="{ width: scorePercent(detail.score) + '%' }"
                                    ></div>
                                </div>
                            </li>
                        </ul>

                        <p v-else class="mt-5 rounded-2xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-400">
                            Tidak ada bidang lain untuk ditampilkan.
                        </p>
                    </section>
                </div>

                <!-- Skor TOPSIS adalah kedekatan RELATIF antar bidang yang dibandingkan, bukan nilai
                     kecocokan mutlak. Tanpa penjelasan ini, angka seperti 90% terbaca sebagai
                     "90% cocok", padahal artinya hanya paling dekat dengan profil ideal di antara pilihan. -->
                <div class="flex items-start gap-3 rounded-2xl bg-sky-50 p-4 text-xs leading-relaxed text-sky-800 ring-1 ring-sky-100">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>
                    <p>
                        Persentase menunjukkan kedekatan hasilmu dengan profil ideal tiap bidang, dibandingkan
                        antar bidang. Gunakan sebagai gambaran awal dan bahan diskusi, bukan keputusan akhir.
                    </p>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

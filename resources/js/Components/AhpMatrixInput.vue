<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
    items: { type: Array, required: true },
    submitUrl: { type: String, required: true },
    idField: { type: String, required: true },
});

const emit = defineEmits(['saved']);

const scaleButtons = [
    { value: 9, label: '9', short: 'Mutlak' },
    { value: 7, label: '7', short: 'Sangat' },
    { value: 5, label: '5', short: 'Lebih' },
    { value: 3, label: '3', short: 'Sedikit' },
    { value: 1, label: 'Sama', short: '' },
    { value: -3, label: '3', short: 'Sedikit' },
    { value: -5, label: '5', short: 'Lebih' },
    { value: -7, label: '7', short: 'Sangat' },
    { value: -9, label: '9', short: 'Mutlak' },
];

const n = computed(() => props.items.length);

function buildEmptyMatrix(size) {
    return Array.from({ length: size }, (_, i) =>
        Array.from({ length: size }, (_, j) => (i === j ? 1 : null))
    );
}

const rawInput = ref(buildEmptyMatrix(n.value));
const result = ref(null);
const errorMessage = ref(null);
const loading = ref(false);

watch(
    () => props.items.map((item) => item.id).join(','),
    () => {
        rawInput.value = buildEmptyMatrix(props.items.length);
        result.value = null;
        errorMessage.value = null;
    }
);

function scaleToDecimal(value) {
    if (value === null) return null;
    return value > 0 ? value : 1 / Math.abs(value);
}

const matrix = computed(() => {
    const size = n.value;
    const m = Array.from({ length: size }, () => Array(size).fill(1));
    for (let i = 0; i < size; i++) {
        for (let j = 0; j < size; j++) {
            if (i === j) {
                m[i][j] = 1;
            } else if (i < j) {
                const decimal = scaleToDecimal(rawInput.value[i][j]);
                m[i][j] = decimal ?? 1;
                m[j][i] = decimal ? 1 / decimal : 1;
            }
        }
    }
    return m;
});

const isComplete = computed(() => {
    const size = n.value;
    for (let i = 0; i < size; i++) {
        for (let j = i + 1; j < size; j++) {
            if (rawInput.value[i][j] === null) return false;
        }
    }
    return true;
});

const pairs = computed(() => {
    const list = [];
    const size = n.value;
    for (let i = 0; i < size; i++) {
        for (let j = i + 1; j < size; j++) {
            list.push({ i, j, itemA: props.items[i], itemB: props.items[j] });
        }
    }
    return list;
});

function selectValue(i, j, value) {
    rawInput.value[i][j] = value;
}

function describeChoice(pair, value) {
    if (value === null) return '';
    if (value === 1) return `${pair.itemA.name} dan ${pair.itemB.name} sama penting`;
    if (value > 0) return `${pair.itemA.name} lebih penting dari ${pair.itemB.name}`;
    return `${pair.itemB.name} lebih penting dari ${pair.itemA.name}`;
}

function describeButton(pair, option) {
    if (option.value === 1) return `${pair.itemA.name} dan ${pair.itemB.name} sama penting`;
    const winner = option.value > 0 ? pair.itemA.name : pair.itemB.name;
    const loser = option.value > 0 ? pair.itemB.name : pair.itemA.name;
    const strength = {
        9: 'mutlak lebih penting',
        7: 'sangat lebih penting',
        5: 'lebih penting',
        3: 'sedikit lebih penting',
    }[Math.abs(option.value)];
    return `${winner} ${strength} dari ${loser}`;
}


function getCsrfHeader() {
    const cookie = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    if (cookie) {
        return { name: 'X-XSRF-TOKEN', value: decodeURIComponent(cookie[1]) };
    }

    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (meta) {
        return { name: 'X-CSRF-TOKEN', value: meta };
    }

    return null;
}


function resolveEndpoint() {
    const url = new URL(props.submitUrl, window.location.origin);
    return url.pathname + url.search;
}

function resolveErrorMessage(status, data) {
    if (status === 401) return 'Sesi login sudah berakhir. Silakan login ulang.';
    if (status === 403) return 'Anda tidak memiliki akses untuk melakukan aksi ini.';
    if (status === 419) return 'Sesi sudah tidak valid. Silakan refresh halaman lalu coba lagi.';
    if (status === 429) return 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.';
    if (status >= 500) return 'Terjadi kesalahan di server. Coba lagi nanti.';

    const firstError = Object.values(data?.errors ?? {}).flat()[0];
    return firstError ?? data?.message ?? 'Terjadi kesalahan saat menyimpan.';
}

function labelFor(code) {
    return props.items.find((item) => item.code === code)?.name ?? code;
}

function formatWeight(weight) {
    return Number(weight).toFixed(4);
}

async function handleSubmit() {
    if (loading.value) return;

    errorMessage.value = null;
    result.value = null;

    const csrf = getCsrfHeader();
    if (!csrf) {
        errorMessage.value = 'Token keamanan (CSRF) tidak ditemukan. Silakan refresh halaman lalu coba lagi.';
        return;
    }

    loading.value = true;

    try {
        const res = await fetch(resolveEndpoint(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                [csrf.name]: csrf.value,
            },
            body: JSON.stringify({
                matrix: matrix.value,
                [props.idField]: props.items.map((item) => item.id),
            }),
        });

        const data = await res.json().catch(() => null);

        if (!res.ok) {
            errorMessage.value = resolveErrorMessage(res.status, data);
            result.value = data?.ahp_preview ?? null;
            return;
        }

        result.value = data?.ahp_result ?? null;
        emit('saved', result.value);
    } catch (error) {
        errorMessage.value = 'Terjadi kesalahan jaringan. Coba lagi.';
    } finally {
        loading.value = false;
    }
}

// ---------------------------------------------------------------
// UI only: tampilan satu pasangan per layar (wizard) + helper warna.
// Tidak mengubah data / perhitungan di atas.
// ---------------------------------------------------------------
const currentIndex = ref(0);
const currentPair = computed(() => pairs.value[currentIndex.value] ?? null);

watch(
    () => props.items.map((item) => item.id).join(','),
    () => {
        currentIndex.value = 0;
    }
);

function goTo(index) {
    if (index < 0 || index >= pairs.value.length) return;
    currentIndex.value = index;
}

let advanceTimer = null;

function pickValue(pair, value) {
    selectValue(pair.i, pair.j, value);

    // lanjut otomatis ke pasangan berikutnya setelah jeda singkat
    clearTimeout(advanceTimer);
    if (currentIndex.value < pairs.value.length - 1) {
        advanceTimer = setTimeout(() => goTo(currentIndex.value + 1), 320);
    }
}

onBeforeUnmount(() => clearTimeout(advanceTimer));

const filledCount = computed(() =>
    pairs.value.filter((p) => rawInput.value[p.i][p.j] !== null).length
);

const progressPercent = computed(() =>
    pairs.value.length ? Math.round((filledCount.value / pairs.value.length) * 100) : 0
);

function shortChoice(pair, value) {
    if (value === null) return 'Belum dipilih';
    if (value === 1) return 'Sama penting';
    return value > 0
        ? `${pair.itemA.name} ${value}`
        : `${pair.itemB.name} ${Math.abs(value)}`;
}

const heightBy = { 9: 'h-[76px]', 7: 'h-16', 5: 'h-14', 3: 'h-12', 1: 'h-11' };

const idleLeft = {
    9: 'bg-indigo-200 text-indigo-800 border-indigo-300 hover:bg-indigo-300',
    7: 'bg-indigo-100 text-indigo-700 border-indigo-200 hover:bg-indigo-200',
    5: 'bg-indigo-50 text-indigo-700 border-indigo-100 hover:bg-indigo-100',
    3: 'bg-indigo-50/60 text-indigo-600 border-indigo-100 hover:bg-indigo-100',
};
const idleRight = {
    9: 'bg-rose-200 text-rose-800 border-rose-300 hover:bg-rose-300',
    7: 'bg-rose-100 text-rose-700 border-rose-200 hover:bg-rose-200',
    5: 'bg-rose-50 text-rose-700 border-rose-100 hover:bg-rose-100',
    3: 'bg-rose-50/60 text-rose-600 border-rose-100 hover:bg-rose-100',
};

function buttonClass(value, selected) {
    const abs = Math.abs(value);
    const size = heightBy[abs];

    if (selected) {
        if (value > 0) return `${size} bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-500/30 -translate-y-0.5`;
        if (value < 0) return `${size} bg-rose-500 text-white border-rose-500 shadow-lg shadow-rose-500/30 -translate-y-0.5`;
        return `${size} bg-slate-700 text-white border-slate-700 shadow-lg shadow-slate-500/30 -translate-y-0.5`;
    }

    if (value > 0) return `${size} ${idleLeft[abs]}`;
    if (value < 0) return `${size} ${idleRight[abs]}`;
    return `${size} bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100`;
}

function chipClass(value) {
    if (value > 0) return 'bg-indigo-50 text-indigo-700 ring-indigo-600/20';
    if (value < 0) return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    return 'bg-slate-100 text-slate-700 ring-slate-500/20';
}

function miniChipClass(value) {
    if (value === null) return 'bg-gray-50 text-gray-400';
    return chipClass(value).replace(' ring-', ' ring-1 ring-inset ring-');
}

const weightColors = ['bg-indigo-500', 'bg-teal-500', 'bg-amber-400', 'bg-rose-400', 'bg-sky-500', 'bg-violet-500'];

function weightColor(index) {
    return weightColors[index % weightColors.length];
}

function weightPercent(weight) {
    return Math.max(0, Math.min(100, Number(weight) * 100));
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- Banner ringkas -->
        <header class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900 via-indigo-700 to-violet-600 px-6 py-5 text-white shadow-lg shadow-indigo-900/20">
            <div class="pointer-events-none absolute -top-16 -right-10 h-48 w-48 rounded-full bg-rose-400/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 left-10 h-48 w-48 rounded-full bg-teal-300/25 blur-3xl"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-xl">
                    <h2 class="text-lg sm:text-xl font-bold tracking-tight">
                        Seberapa penting satu hal dibanding yang lain?
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm leading-relaxed text-indigo-100">
                        Untuk tiap pasang, klik titik pada garis sesuai seberapa penting
                        menurut Anda. Klik "Sama" di tengah kalau keduanya menurut Anda setara.
                    </p>
                </div>
                <div class="w-full sm:w-52 shrink-0">
                    <div class="flex items-center justify-between text-xs font-medium text-indigo-50">
                        <span>Progres</span>
                        <span>{{ filledCount }} dari {{ pairs.length }} pasang</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-white/20 overflow-hidden" role="progressbar"
                         :aria-valuenow="progressPercent" aria-valuemin="0" aria-valuemax="100"
                         aria-label="Progres pengisian perbandingan">
                        <div class="h-full rounded-full bg-gradient-to-r from-teal-300 to-amber-300 transition-all duration-500 motion-reduce:transition-none"
                             :style="{ width: progressPercent + '%' }"></div>
                    </div>
                </div>
            </div>
        </header>

        <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-5">

            <!-- ===== KIRI: satu pasangan aktif ===== -->
            <section v-if="currentPair" class="lg:col-span-3 rounded-3xl bg-white p-5 sm:p-6 ring-1 ring-gray-200/80 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-400">
                        Pasangan {{ currentIndex + 1 }} dari {{ pairs.length }}
                    </span>

                    <!-- titik navigasi -->
                    <div class="flex items-center gap-1.5" role="group" aria-label="Pilih pasangan">
                        <button
                            v-for="(p, idx) in pairs"
                            :key="'dot-' + idx"
                            type="button"
                            @click="goTo(idx)"
                            :aria-label="`Pasangan ${idx + 1}`"
                            :aria-current="idx === currentIndex ? 'step' : undefined"
                            class="h-2.5 rounded-full transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 motion-reduce:transition-none"
                            :class="idx === currentIndex
                                ? 'w-6 bg-indigo-500'
                                : rawInput[p.i][p.j] !== null
                                    ? 'w-2.5 bg-emerald-400'
                                    : 'w-2.5 bg-gray-200 hover:bg-gray-300'"
                        ></button>
                    </div>
                </div>

                <!-- A vs B -->
                <div class="mt-4 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                    <div class="rounded-2xl bg-indigo-50 px-4 py-4 text-left ring-1 ring-indigo-100">
                        <p class="text-base font-bold text-indigo-800 leading-snug">{{ currentPair.itemA.name }}</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-[11px] font-bold text-gray-500">VS</span>
                    <div class="rounded-2xl bg-rose-50 px-4 py-4 text-right ring-1 ring-rose-100">
                        <p class="text-base font-bold text-rose-800 leading-snug">{{ currentPair.itemB.name }}</p>
                    </div>
                </div>

                <!-- Skala -->
                <div
                    class="mt-6 flex items-end justify-between gap-1 sm:gap-1.5"
                    role="group"
                    :aria-label="`Perbandingan ${currentPair.itemA.name} vs ${currentPair.itemB.name}`"
                >
                    <button
                        v-for="option in scaleButtons"
                        :key="option.value"
                        type="button"
                        :title="describeButton(currentPair, option)"
                        :aria-label="describeButton(currentPair, option)"
                        :aria-pressed="rawInput[currentPair.i][currentPair.j] === option.value"
                        @click="pickValue(currentPair, option.value)"
                        class="flex flex-1 flex-col items-center justify-center gap-0.5 rounded-xl border text-xs font-medium transition duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 motion-reduce:transition-none"
                        :class="buttonClass(option.value, rawInput[currentPair.i][currentPair.j] === option.value)"
                    >
                        <span class="text-sm font-bold">{{ option.label }}</span>
                        <span v-if="option.short" class="hidden sm:block text-[10px] leading-none opacity-80">{{ option.short }}</span>
                    </button>
                </div>

                <div class="mt-2 flex justify-between text-[11px] font-medium">
                    <span class="text-indigo-500">← {{ currentPair.itemA.name }} lebih penting</span>
                    <span class="text-rose-500">{{ currentPair.itemB.name }} lebih penting →</span>
                </div>

                <!-- Pilihan saat ini -->
                <div class="mt-5 flex min-h-[30px] justify-center" aria-live="polite">
                    <span
                        v-if="rawInput[currentPair.i][currentPair.j] !== null"
                        class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset"
                        :class="chipClass(rawInput[currentPair.i][currentPair.j])"
                    >
                        {{ describeChoice(currentPair, rawInput[currentPair.i][currentPair.j]) }}
                    </span>
                    <span v-else class="rounded-full bg-gray-50 px-3 py-1 text-xs text-gray-400">Belum dipilih</span>
                </div>

                <!-- Navigasi -->
                <div class="mt-5 flex items-center justify-between border-t border-gray-100 pt-4">
                    <button
                        type="button"
                        @click="goTo(currentIndex - 1)"
                        :disabled="currentIndex === 0"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent"
                    >
                        ← Sebelumnya
                    </button>
                    <button
                        type="button"
                        @click="goTo(currentIndex + 1)"
                        :disabled="currentIndex === pairs.length - 1"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent"
                    >
                        Berikutnya →
                    </button>
                </div>
            </section>

            <!-- ===== KANAN: ringkasan + simpan + hasil ===== -->
            <aside class="lg:col-span-2 space-y-4">
                <section class="rounded-3xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-800">Ringkasan jawaban</h3>

                    <ul class="mt-3 max-h-64 space-y-1.5 overflow-y-auto pr-1">
                        <li v-for="(p, idx) in pairs" :key="'sum-' + idx">
                            <button
                                type="button"
                                @click="goTo(idx)"
                                class="flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                :class="idx === currentIndex ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                            >
                                <span class="min-w-0 truncate text-xs text-gray-600">
                                    <span class="font-semibold text-gray-400">{{ idx + 1 }}.</span>
                                    {{ p.itemA.name }} <span class="text-gray-300">vs</span> {{ p.itemB.name }}
                                </span>
                                <span
                                    class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :class="miniChipClass(rawInput[p.i][p.j])"
                                >
                                    {{ shortChoice(p, rawInput[p.i][p.j]) }}
                                </span>
                            </button>
                        </li>
                    </ul>

                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <p class="mb-3 text-xs text-gray-500">
                            <span v-if="isComplete" class="font-medium text-emerald-600">Semua pasangan sudah terisi</span>
                            <span v-else>
                                Sisa <span class="font-semibold text-gray-800">{{ pairs.length - filledCount }}</span> pasang lagi
                            </span>
                        </p>
                        <button
                            type="button"
                            :disabled="!isComplete || loading"
                            @click="handleSubmit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                        >
                            <svg v-if="loading" class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                            </svg>
                            {{ loading ? 'Menghitung…' : 'Hitung & Simpan Bobot' }}
                        </button>
                    </div>
                </section>

                <!-- Error -->
                <div
                    v-if="errorMessage"
                    class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                    role="alert"
                >
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Hasil -->
                <section v-if="result" class="rounded-3xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-800">Hasil perhitungan</h3>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset"
                            :class="result.is_consistent
                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                                : 'bg-rose-50 text-rose-700 ring-rose-600/20'"
                        >
                            {{ result.is_consistent ? 'Konsisten' : 'Tidak konsisten' }}
                        </span>
                    </div>

                    <ul class="mt-4 space-y-3">
                        <li v-for="(weight, code, idx) in result.weights" :key="code">
                            <div class="flex items-baseline justify-between text-xs">
                                <span class="font-medium text-gray-700">
                                    {{ labelFor(code) }}
                                    <span class="ml-1 font-normal text-gray-400">({{ code }})</span>
                                </span>
                                <span class="font-mono text-gray-500">{{ formatWeight(weight) }}</span>
                            </div>
                            <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full" :class="weightColor(idx)" :style="{ width: weightPercent(weight) + '%' }"></div>
                            </div>
                        </li>
                    </ul>

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <div class="rounded-xl bg-indigo-50 p-2 text-center">
                            <p class="text-[10px] font-medium text-indigo-600">λmax</p>
                            <p class="mt-0.5 text-xs font-bold text-indigo-900">{{ result.lambda_max }}</p>
                        </div>
                        <div class="rounded-xl bg-teal-50 p-2 text-center">
                            <p class="text-[10px] font-medium text-teal-600">CI</p>
                            <p class="mt-0.5 text-xs font-bold text-teal-900">{{ result.ci }}</p>
                        </div>
                        <div class="rounded-xl p-2 text-center" :class="result.is_consistent ? 'bg-emerald-50' : 'bg-rose-50'">
                            <p class="text-[10px] font-medium" :class="result.is_consistent ? 'text-emerald-600' : 'text-rose-600'">CR</p>
                            <p class="mt-0.5 text-xs font-bold" :class="result.is_consistent ? 'text-emerald-900' : 'text-rose-900'">{{ result.cr }}</p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>

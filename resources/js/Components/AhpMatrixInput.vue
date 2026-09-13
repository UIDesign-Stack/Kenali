<script setup>
import { ref, computed, watch } from 'vue';

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

watch(
    () => props.items,
    (newItems, oldItems) => {
        if (newItems.length !== oldItems?.length) {
            rawInput.value = buildEmptyMatrix(newItems.length);
        }
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

const result = ref(null);
const errorMessage = ref(null);
const loading = ref(false);

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? null;
}

async function handleSubmit() {
    errorMessage.value = null;
    result.value = null;

    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        errorMessage.value = 'Token keamanan (CSRF) tidak ditemukan. Silakan refresh halaman lalu coba lagi.';
        return;
    }

    loading.value = true;

    try {
        const res = await fetch(props.submitUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                matrix: matrix.value,
                [props.idField]: props.items.map((item) => item.id),
            }),
        });

        if (res.status === 419) {
            errorMessage.value = 'Sesi login sudah tidak valid. Silakan refresh halaman lalu coba lagi.';
            return;
        }

        const data = await res.json();

        if (!res.ok) {
            errorMessage.value = data.errors?.matrix?.[0] ?? data.message ?? 'Terjadi kesalahan saat menyimpan.';
            result.value = data.ahp_preview ?? null;
            return;
        }

        result.value = data.ahp_result ?? null;
        emit('saved', result.value);
    } catch (error) {
        console.error('Error saat submit:', error);
        errorMessage.value = 'Terjadi kesalahan jaringan. Coba lagi.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="max-w-3xl mx-auto p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">
            Seberapa penting satu hal dibanding yang lain?
        </h2>
        <p class="text-sm text-gray-500 mb-8">
            Untuk tiap pasang di bawah, klik titik pada garis sesuai seberapa penting
            menurut Anda. Klik "Sama" di tengah kalau keduanya menurut Anda setara.
        </p>

        <div class="space-y-8">
            <div
                v-for="pair in pairs"
                :key="'cell-' + pair.itemA.id + '-' + pair.itemB.id"
                class="border border-gray-200 rounded-lg p-5"
            >
                <div class="flex justify-between text-sm font-semibold text-gray-700 mb-4">
                    <span>{{ pair.itemA.name }}</span>
                    <span>{{ pair.itemB.name }}</span>
                </div>

                <div class="flex items-center justify-between gap-1" role="group" :aria-label="`Perbandingan ${pair.itemA.name} vs ${pair.itemB.name}`">
                    <button
                        v-for="option in scaleButtons"
                        :key="option.value"
                        type="button"
                        :title="describeButton(pair, option)"
                        :aria-label="describeButton(pair, option)"
                        :aria-pressed="rawInput[pair.i][pair.j] === option.value"
                        @click="selectValue(pair.i, pair.j, option.value)"
                        class="flex-1 h-14 rounded-md text-xs font-medium border transition flex flex-col items-center justify-center gap-0.5"
                        :class="rawInput[pair.i][pair.j] === option.value
                            ? 'bg-teal-600 text-white border-teal-600'
                            : 'bg-white text-gray-500 border-gray-200 hover:border-teal-300'"
                    >
                        <span class="text-sm font-semibold">{{ option.label }}</span>
                        <span v-if="option.short" class="text-[10px] opacity-80 leading-none">{{ option.short }}</span>
                    </button>
                </div>

                <p class="mt-3 text-xs text-gray-500 text-center min-h-[16px]" aria-live="polite">
                    {{ describeChoice(pair, rawInput[pair.i][pair.j]) || 'Belum dipilih' }}
                </p>
            </div>
        </div>

        <div v-if="errorMessage" class="mt-6 p-3 rounded-md bg-red-50 text-red-700 text-sm" role="alert">
            {{ errorMessage }}
        </div>

        <div v-if="result" class="mt-4 p-4 rounded-md bg-teal-50 text-sm">
            <p class="font-medium text-teal-800 mb-2">Hasil perhitungan:</p>
            <ul class="space-y-1 text-teal-700">
                <li v-for="(weight, code) in result.weights" :key="code">
                    {{ code }}: {{ weight }}
                </li>
            </ul>
            <p class="mt-2 text-teal-700">
                λmax = {{ result.lambda_max }} · CI = {{ result.ci }} · CR = {{ result.cr }}
                <span :class="result.is_consistent ? 'text-green-600' : 'text-red-600'">
                    ({{ result.is_consistent ? 'Konsisten' : 'Tidak konsisten' }})
                </span>
            </p>
        </div>

        <button
            type="button"
            :disabled="!isComplete || loading"
            @click="handleSubmit"
            class="mt-6 px-5 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed hover:bg-teal-700"
        >
            {{ loading ? 'Menghitung…' : 'Hitung & Simpan Bobot' }}
        </button>
    </div>
</template>
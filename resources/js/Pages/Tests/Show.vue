<script setup>
import { ref, reactive, computed, onBeforeUnmount } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    testSession: { type: Object, required: true },
    questions: { type: Array, required: true },

    existingAnswers: { type: [Object, Array], required: true },
});

const answers = ref({ ...props.existingAnswers });

const failedQuestionIds = reactive(new Set());

const inactiveQuestionIds = reactive(new Set());

const findFirstUnanswered = () => {
    const index = props.questions.findIndex((q) => !(q.id in answers.value));
    return index === -1 ? 0 : index;
};

const currentIndex = ref(findFirstUnanswered());
const saving = ref(false);
const completing = ref(false);
const errorMessage = ref(null);

let advanceTimeout = null;
let redirectTimeout = null;

const currentQuestion = computed(() => props.questions[currentIndex.value]);
const totalQuestions = computed(() => props.questions.length);
const isLastQuestion = computed(() => currentIndex.value === totalQuestions.value - 1);
const hasFailedSaves = computed(() => failedQuestionIds.size > 0);

const requiredQuestions = computed(() =>
    props.questions.filter((q) => !inactiveQuestionIds.has(q.id))
);
const answeredCount = computed(() =>
    requiredQuestions.value.filter((q) => q.id in answers.value).length
);
const progressPercent = computed(() =>
    requiredQuestions.value.length === 0
        ? 100
        : Math.round((answeredCount.value / requiredQuestions.value.length) * 100)
);
const isComplete = computed(() => answeredCount.value >= requiredQuestions.value.length);

const scaleOptions = [
    { value: 1, label: 'Sangat Tidak Setuju' },
    { value: 2, label: 'Tidak Setuju' },
    { value: 3, label: 'Netral' },
    { value: 4, label: 'Setuju' },
    { value: 5, label: 'Sangat Setuju' },
];


function csrfHeaders() {
    const cookie = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    if (cookie) {
        return { 'X-XSRF-TOKEN': decodeURIComponent(cookie[1]) };
    }

    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return meta ? { 'X-CSRF-TOKEN': meta } : {};
}

function describeSaveError(status) {
    if (status === 401) return 'Sesi login sudah berakhir. Silakan login ulang; jawabanmu yang sudah tersimpan tidak hilang.';
    if (status === 419) return 'Sesi sudah tidak valid. Muat ulang halaman; jawabanmu yang sudah tersimpan tidak hilang.';
    if (status === 429) return 'Terlalu cepat. Tunggu sebentar, lalu klik "Coba simpan lagi" pada soal yang bertanda merah.';

    return 'Beberapa jawaban gagal tersimpan karena masalah koneksi. Cek titik merah di bawah.';
}

function cancelPendingAdvance() {
    if (advanceTimeout) {
        clearTimeout(advanceTimeout);
        advanceTimeout = null;
    }
}

function scheduleAdvance() {
    cancelPendingAdvance();
    advanceTimeout = setTimeout(() => {
        if (!isLastQuestion.value) {
            currentIndex.value++;
        }
        advanceTimeout = null;
    }, 250);
}

onBeforeUnmount(() => {
    cancelPendingAdvance();
    clearTimeout(redirectTimeout);
});

async function selectAnswer(value) {
    const question = currentQuestion.value;
    if (!question || inactiveQuestionIds.has(question.id)) return;

    answers.value[question.id] = value;
    saving.value = true;
    errorMessage.value = null;

    try {
        const response = await fetch(route('tests.answers.save', props.testSession.id), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...csrfHeaders(),
            },
            body: JSON.stringify({
                question_id: question.id,
                answer_value: value,
            }),
        });

        if (response.status === 422) {
            const data = await response.json().catch(() => null);

            if (data?.errors?.question_id) {
                inactiveQuestionIds.add(question.id);
                failedQuestionIds.delete(question.id);
                errorMessage.value = 'Soal ini sudah tidak berlaku dan dilewati otomatis dari daftar wajib.';
                saving.value = false;
                scheduleAdvance();
                return;
            }

            failedQuestionIds.delete(question.id);
            errorMessage.value = (data?.message ?? 'Sesi tes ini sudah tidak aktif.') + ' Mengalihkan…';
            saving.value = false;
            redirectTimeout = setTimeout(() => router.visit(route('tests.show', props.testSession.id)), 1500);
            return;
        }

        if (!response.ok) {
            failedQuestionIds.add(question.id);
            errorMessage.value = describeSaveError(response.status);
            saving.value = false;
            return;
        }

        failedQuestionIds.delete(question.id);
    } catch (e) {
        failedQuestionIds.add(question.id);
        errorMessage.value = describeSaveError(0);
    } finally {
        saving.value = false;
    }

    scheduleAdvance();
}

async function retryFailedSave(questionId) {
    const question = props.questions.find((q) => q.id === questionId);
    if (!question || !(questionId in answers.value)) return;

    const previousIndex = currentIndex.value;
    currentIndex.value = props.questions.indexOf(question);
    cancelPendingAdvance();
    await selectAnswer(answers.value[questionId]);
    currentIndex.value = previousIndex;
}

function goToQuestion(index) {
    cancelPendingAdvance();
    currentIndex.value = index;
}

function goPrevious() {
    cancelPendingAdvance();
    if (currentIndex.value > 0) currentIndex.value--;
}

function goNext() {
    cancelPendingAdvance();
    if (!isLastQuestion.value) currentIndex.value++;
}

function finishTest() {
    if (hasFailedSaves.value) {
        errorMessage.value = 'Masih ada jawaban yang gagal tersimpan. Perbaiki dulu sebelum menyelesaikan tes.';
        return;
    }

    if (completing.value) return;

    completing.value = true;
    errorMessage.value = null;

    router.post(route('tests.complete', props.testSession.id), {}, {
        onError: (errors) => {
            errorMessage.value = errors.complete ?? 'Gagal menyelesaikan tes.';

            router.reload({
                only: ['questions', 'existingAnswers'],
                preserveScroll: true,
                onSuccess: () => {
                    answers.value = { ...props.existingAnswers, ...answers.value };
                    inactiveQuestionIds.clear();
                    currentIndex.value = findFirstUnanswered();
                },
            });
        },
        onFinish: () => (completing.value = false),
    });
}

const optionStyles = {
    1: {
        idle: 'bg-rose-50 text-rose-700 border-rose-100 hover:bg-rose-100 hover:border-rose-200',
        on: 'bg-rose-500 text-white border-rose-500 shadow-lg shadow-rose-500/30',
    },
    2: {
        idle: 'bg-orange-50 text-orange-700 border-orange-100 hover:bg-orange-100 hover:border-orange-200',
        on: 'bg-orange-400 text-white border-orange-400 shadow-lg shadow-orange-400/30',
    },
    3: {
        idle: 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100',
        on: 'bg-slate-600 text-white border-slate-600 shadow-lg shadow-slate-500/30',
    },
    4: {
        idle: 'bg-teal-50 text-teal-700 border-teal-100 hover:bg-teal-100 hover:border-teal-200',
        on: 'bg-teal-500 text-white border-teal-500 shadow-lg shadow-teal-500/30',
    },
    5: {
        idle: 'bg-indigo-50 text-indigo-700 border-indigo-100 hover:bg-indigo-100 hover:border-indigo-200',
        on: 'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-500/30',
    },
};

function optionClass(value, selected) {
    return selected ? optionStyles[value].on : optionStyles[value].idle;
}

function chipClass(q, index) {
    const base = index === currentIndex.value ? 'ring-2 ring-indigo-400 ring-offset-1 ' : '';

    if (inactiveQuestionIds.has(q.id)) return base + 'bg-gray-200 text-gray-400 line-through';
    if (failedQuestionIds.has(q.id)) return base + 'bg-rose-500 text-white';
    if (q.id in answers.value) return base + 'bg-teal-500 text-white';
    return base + 'bg-gray-100 text-gray-500 hover:bg-gray-200';
}

function chipLabel(q, index) {
    if (inactiveQuestionIds.has(q.id)) return `Soal ${index + 1}, sudah tidak berlaku`;
    if (failedQuestionIds.has(q.id)) return `Soal ${index + 1}, gagal tersimpan`;
    if (q.id in answers.value) return `Soal ${index + 1}, sudah dijawab`;
    return `Soal ${index + 1}, belum dijawab`;
}

function chipTitle(q, index) {
    if (inactiveQuestionIds.has(q.id)) return `Soal ${index + 1} — sudah tidak berlaku`;
    if (failedQuestionIds.has(q.id)) return `Soal ${index + 1} — gagal tersimpan`;
    return `Soal ${index + 1}`;
}
</script>

<template>
    <Head title="Isi Tes" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Isi Tes</h1>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

            <!-- Progres -->
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900 via-indigo-700 to-violet-600 px-6 py-5 text-white shadow-lg shadow-indigo-900/20">
                <div class="pointer-events-none absolute -top-16 -right-10 h-48 w-48 rounded-full bg-rose-400/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-10 h-48 w-48 rounded-full bg-teal-300/25 blur-3xl"></div>

                <div class="relative">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="font-semibold">Soal {{ currentIndex + 1 }} dari {{ totalQuestions }}</span>
                        <span class="text-indigo-100">
                            {{ answeredCount }}/{{ requiredQuestions.length }} terjawab
                            <span v-if="saving" class="ml-1 text-amber-200">· menyimpan…</span>
                        </span>
                    </div>
                    <div
                        class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-white/20"
                        role="progressbar"
                        :aria-valuenow="progressPercent"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="Kemajuan pengisian tes"
                    >
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-teal-300 to-amber-300 transition-all duration-300 motion-reduce:transition-none"
                            :style="{ width: progressPercent + '%' }"
                        ></div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">

                <!-- ===== Kiri: soal + navigasi ===== -->
                <div class="lg:col-span-3">

                    <!-- Kartu soal nonaktif -->
                    <div
                        v-if="currentQuestion && inactiveQuestionIds.has(currentQuestion.id)"
                        class="rounded-3xl border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-sm text-gray-500"
                    >
                        Soal ini sudah tidak berlaku lagi dan tidak perlu dijawab.
                    </div>

                    <!-- Kartu soal -->
                    <section
                        v-else-if="currentQuestion"
                        class="rounded-3xl bg-white p-6 ring-1 ring-gray-200/80 shadow-sm sm:p-8"
                    >
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                                {{ currentIndex + 1 }}
                            </span>
                            <span
                                v-if="currentQuestion.sub_criteria?.name"
                                class="rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-600/20"
                            >
                                {{ currentQuestion.sub_criteria?.name }}
                            </span>
                        </div>

                        <p class="mt-5 text-xl font-semibold leading-relaxed text-gray-900 sm:text-2xl">
                            {{ currentQuestion.question_text }}
                        </p>

                        <p
                            v-if="failedQuestionIds.has(currentQuestion.id)"
                            class="mt-4 rounded-xl bg-rose-50 px-4 py-2.5 text-sm text-rose-700 ring-1 ring-rose-200"
                            role="alert"
                        >
                            Jawaban soal ini gagal tersimpan.
                            <button type="button" @click="retryFailedSave(currentQuestion.id)" class="font-semibold underline">
                                Coba simpan lagi
                            </button>
                        </p>

                        <div
                            class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-5"
                            role="group"
                            aria-label="Pilihan jawaban"
                        >
                            <button
                                v-for="option in scaleOptions"
                                :key="option.value"
                                type="button"
                                @click="selectAnswer(option.value)"
                                :disabled="saving"
                                :aria-pressed="answers[currentQuestion.id] === option.value"
                                class="flex items-center justify-between gap-2 rounded-2xl border px-4 py-3 text-sm font-medium transition duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:opacity-50 motion-reduce:transition-none sm:flex-col sm:justify-center sm:py-5 sm:text-center"
                                :class="[
                                    optionClass(option.value, answers[currentQuestion.id] === option.value),
                                    answers[currentQuestion.id] === option.value ? 'sm:-translate-y-1' : '',
                                ]"
                            >
                                <span class="text-2xl font-bold sm:order-first sm:text-3xl">{{ option.value }}</span>
                                <span class="text-xs leading-tight sm:mt-1 sm:text-[11px]">{{ option.label }}</span>
                            </button>
                        </div>
                    </section>

                    <!-- Navigasi -->
                    <div class="mt-5 flex items-center justify-between">
                        <button
                            type="button"
                            @click="goPrevious"
                            :disabled="currentIndex === 0"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-white hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:shadow-none"
                        >
                            ← Sebelumnya
                        </button>

                        <button
                            v-if="isLastQuestion"
                            type="button"
                            @click="finishTest"
                            :disabled="completing || !isComplete || hasFailedSaves"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                        >
                            <svg v-if="completing" class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                            </svg>
                            {{ completing ? 'Menyelesaikan…' : 'Selesai & Lihat Hasil' }}
                        </button>

                        <button
                            v-else
                            type="button"
                            @click="goNext"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium text-indigo-600 transition hover:bg-white hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        >
                            Lewati →
                        </button>
                    </div>

                    <p
                        v-if="errorMessage"
                        class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-center text-sm text-rose-700"
                        role="alert"
                    >
                        {{ errorMessage }}
                    </p>
                </div>

                <!-- ===== Kanan: peta soal ===== -->
                <aside class="lg:col-span-1">
                    <div class="rounded-3xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm lg:sticky lg:top-24">
                        <h2 class="text-sm font-semibold text-gray-800">Peta soal</h2>

                        <div class="mt-4 grid grid-cols-6 gap-2 sm:grid-cols-8 lg:grid-cols-5">
                            <button
                                v-for="(q, index) in questions"
                                :key="q.id"
                                type="button"
                                @click="goToQuestion(index)"
                                class="flex aspect-square items-center justify-center rounded-lg text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                :class="chipClass(q, index)"
                                :aria-label="chipLabel(q, index)"
                                :title="chipTitle(q, index)"
                            >
                                {{ index + 1 }}
                            </button>
                        </div>

                        <ul class="mt-5 space-y-1.5 text-[11px] text-gray-500">
                            <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded bg-teal-500"></span>Sudah dijawab</li>
                            <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded bg-gray-200"></span>Belum dijawab</li>
                            <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded bg-rose-500"></span>Gagal tersimpan</li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

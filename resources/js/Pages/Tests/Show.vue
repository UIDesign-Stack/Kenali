<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    testSession: { type: Object, required: true },
    questions: { type: Array, required: true },
    existingAnswers: { type: Object, required: true },
});

const answers = ref({ ...props.existingAnswers });

const failedQuestionIds = reactive(new Set());

const findFirstUnanswered = () => {
    const index = props.questions.findIndex((q) => !(q.id in answers.value));
    return index === -1 ? 0 : index;
};

const currentIndex = ref(findFirstUnanswered());
const saving = ref(false);
const completing = ref(false);
const errorMessage = ref(null);

let advanceTimeout = null;

const currentQuestion = computed(() => props.questions[currentIndex.value]);
const totalQuestions = computed(() => props.questions.length);
const answeredCount = computed(() => Object.keys(answers.value).length);
const progressPercent = computed(() =>
    Math.round((answeredCount.value / totalQuestions.value) * 100)
);
const isLastQuestion = computed(() => currentIndex.value === totalQuestions.value - 1);
const hasFailedSaves = computed(() => failedQuestionIds.size > 0);

const scaleOptions = [
    { value: 1, label: 'Sangat Tidak Setuju' },
    { value: 2, label: 'Tidak Setuju' },
    { value: 3, label: 'Netral' },
    { value: 4, label: 'Setuju' },
    { value: 5, label: 'Sangat Setuju' },
];

function cancelPendingAdvance() {
    if (advanceTimeout) {
        clearTimeout(advanceTimeout);
        advanceTimeout = null;
    }
}

async function selectAnswer(value) {
    const question = currentQuestion.value;
    answers.value[question.id] = value;
    saving.value = true;
    errorMessage.value = null;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(route('tests.answers.save', props.testSession.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                question_id: question.id,
                answer_value: value,
            }),
        });

        if (!response.ok) throw new Error('Response tidak OK');

        failedQuestionIds.delete(question.id);
    } catch (e) {
        console.error('Gagal menyimpan jawaban:', e);
        failedQuestionIds.add(question.id);
        errorMessage.value = 'Beberapa jawaban gagal tersimpan karena masalah koneksi. Cek titik merah di bawah.';
    } finally {
        saving.value = false;
    }

    cancelPendingAdvance();
    advanceTimeout = setTimeout(() => {
        if (!isLastQuestion.value) {
            currentIndex.value++;
        }
        advanceTimeout = null;
    }, 250);
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

    completing.value = true;
    errorMessage.value = null;

    router.post(route('tests.complete', props.testSession.id), {}, {
        onError: (errors) => {
            errorMessage.value = errors.complete ?? 'Gagal menyelesaikan tes.';
            completing.value = false;
        },
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Isi Tes</h1>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <!-- Progress bar -->
            <div class="mb-8">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Soal {{ currentIndex + 1 }} dari {{ totalQuestions }}</span>
                    <span>
                        {{ answeredCount }}/{{ totalQuestions }} terjawab
                        <span v-if="saving" class="text-teal-500">· menyimpan…</span>
                    </span>
                </div>
                <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-teal-600 transition-all duration-300"
                        :style="{ width: progressPercent + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Kartu soal -->
            <div v-if="currentQuestion" class="p-6 border border-gray-200 rounded-lg">
                <p class="text-xs text-teal-600 font-medium mb-2">
                    {{ currentQuestion.sub_criteria?.name }}
                </p>
                <p class="text-base text-gray-800 mb-6">{{ currentQuestion.question_text }}</p>

                <p v-if="failedQuestionIds.has(currentQuestion.id)" class="text-xs text-red-600 mb-3">
                    Jawaban soal ini gagal tersimpan.
                    <button type="button" @click="retryFailedSave(currentQuestion.id)" class="underline font-medium">
                        Coba simpan lagi
                    </button>
                </p>

                <div class="space-y-2">
                    <button
                        v-for="option in scaleOptions"
                        :key="option.value"
                        type="button"
                        @click="selectAnswer(option.value)"
                        :disabled="saving"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-md border text-sm transition disabled:opacity-50"
                        :class="answers[currentQuestion.id] === option.value
                            ? 'bg-teal-600 text-white border-teal-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-teal-300'"
                    >
                        <span>{{ option.label }}</span>
                        <span class="text-xs opacity-70">{{ option.value }}</span>
                    </button>
                </div>
            </div>

            <!-- Navigasi -->
            <div class="flex items-center justify-between mt-6">
                <button
                    type="button"
                    @click="goPrevious"
                    :disabled="currentIndex === 0"
                    class="text-sm text-gray-500 disabled:opacity-30 hover:text-gray-700"
                >
                    ← Sebelumnya
                </button>

                <button
                    v-if="isLastQuestion"
                    type="button"
                    @click="finishTest"
                    :disabled="completing || answeredCount < totalQuestions || hasFailedSaves"
                    class="px-5 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    {{ completing ? 'Menyelesaikan…' : 'Selesai & Lihat Hasil' }}
                </button>

                <button
                    v-else
                    type="button"
                    @click="goNext"
                    class="text-sm text-gray-500 hover:text-gray-700"
                >
                    Lewati →
                </button>
            </div>

            <p v-if="errorMessage" class="mt-4 text-sm text-red-600 text-center">{{ errorMessage }}</p>

            <!-- Navigasi cepat titik-titik -->
            <div class="flex flex-wrap gap-1.5 mt-8 justify-center">
                <button
                    v-for="(q, index) in questions"
                    :key="q.id"
                    @click="goToQuestion(index)"
                    class="w-2.5 h-2.5 rounded-full transition"
                    :class="[
                        index === currentIndex ? 'ring-2 ring-teal-400' : '',
                        failedQuestionIds.has(q.id) ? 'bg-red-500' : (q.id in answers ? 'bg-teal-500' : 'bg-gray-200'),
                    ]"
                    :title="failedQuestionIds.has(q.id) ? `Soal ${index + 1} — gagal tersimpan` : `Soal ${index + 1}`"
                ></button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
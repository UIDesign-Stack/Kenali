<script setup>
import { computed, ref } from 'vue';
import { router, Link, useForm, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    subCriteria: { type: Object, required: true },
    questions: { type: Array, required: true },
});


const MIN_LENGTH = 5;
const MAX_LENGTH = 1000;

const page = usePage();

// supaya pesan lama tidak menempel setelah aksi lain berhasil.
const actionError = ref('');

const newQuestionForm = useForm({
    question_text: '',
});

const canSubmitNew = computed(() => newQuestionForm.question_text.trim().length >= MIN_LENGTH);

function submitNewQuestion() {
    if (!canSubmitNew.value) return;

    actionError.value = '';

    newQuestionForm.post(route('admin.questions.store', props.subCriteria.id), {
        preserveScroll: true,
        onSuccess: () => newQuestionForm.reset(),
    });
}

const editingId = ref(null);
const editText = ref('');
const editError = ref('');
const editSaving = ref(false);

function startEdit(question) {
    editingId.value = question.id;
    editText.value = question.question_text;
    editError.value = '';
    actionError.value = '';
}

function cancelEdit() {
    editingId.value = null;
    editText.value = '';
    editError.value = '';
}

function saveEdit(question) {
    const text = editText.value.trim();

    if (text.length < MIN_LENGTH) {
        editError.value = `Teks soal minimal ${MIN_LENGTH} karakter.`;
        return;
    }

    editSaving.value = true;
    editError.value = '';

    router.patch(route('admin.questions.update', question.id), {
        question_text: text,
    }, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
        onError: (errors) => {
            editError.value = errors.question_text || 'Gagal menyimpan perubahan.';
        },
        onFinish: () => (editSaving.value = false),
    });
}

const togglingId = ref(null);
const destroyingId = ref(null);


const rowActionRunning = computed(() => togglingId.value !== null || destroyingId.value !== null);

function toggleActive(question) {
    if (rowActionRunning.value) return;

    togglingId.value = question.id;
    actionError.value = '';

    router.patch(route('admin.questions.toggle-active', question.id), {}, {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = errors.question || 'Gagal mengubah status soal.';
        },
        onFinish: () => (togglingId.value = null),
    });
}

function destroyQuestion(question) {
    if (rowActionRunning.value) return;
    if (!confirm('Yakin ingin menghapus soal ini?')) return;

    destroyingId.value = question.id;
    actionError.value = '';

    router.delete(route('admin.questions.destroy', question.id), {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = errors.question || 'Gagal menghapus soal.';
        },
        onFinish: () => (destroyingId.value = null),
    });
}

// ---------------------------------------------------------------
// UI only: ringkasan jumlah soal
// ---------------------------------------------------------------
const totalCount = computed(() => props.questions.length);
const activeTotal = computed(() => props.questions.filter((q) => q.is_active).length);
const inactiveTotal = computed(() => totalCount.value - activeTotal.value);
</script>

<template>
    <Head :title="`Soal — ${subCriteria.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('admin.questions.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← Bank Soal
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Soal
                    <span class="ml-1 inline-flex items-center rounded-lg bg-gradient-to-r from-indigo-100 to-violet-100 px-2.5 py-0.5 text-indigo-700">{{ subCriteria.name }}</span>
                </h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <!-- Notifikasi -->
            <div
                v-if="page.props.flash?.success"
                class="mb-4 flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200"
                role="status"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </span>
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="actionError"
                class="mb-4 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                role="alert"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ actionError }}</span>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

                <!-- ===== KIRI: form tambah soal (menempel saat scroll) ===== -->
                <aside class="lg:col-span-2">
                    <div class="space-y-4 lg:sticky lg:top-24">
                        <form @submit.prevent="submitNewQuestion" class="rounded-3xl bg-white p-5 sm:p-6 ring-1 ring-gray-200/80 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500 text-white shadow-md shadow-indigo-500/30">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                </span>
                                <label for="new-question" class="text-base font-semibold text-gray-800">
                                    Tambah soal baru
                                </label>
                            </div>

                            <textarea
                                id="new-question"
                                v-model="newQuestionForm.question_text"
                                rows="5"
                                :maxlength="MAX_LENGTH"
                                placeholder="Contoh: Saya senang menganalisis data untuk menemukan pola tertentu."
                                class="mt-4 w-full resize-none rounded-2xl border-gray-200 bg-gray-50 text-sm placeholder:text-gray-400 focus:border-indigo-400 focus:bg-white focus:ring-indigo-400"
                                :aria-invalid="!!newQuestionForm.errors.question_text"
                                aria-describedby="new-question-error"
                            ></textarea>

                            <div class="mt-1.5 flex items-center justify-between text-xs">
                                <span :class="canSubmitNew || !newQuestionForm.question_text ? 'text-gray-400' : 'text-amber-600'">
                                    Minimal {{ MIN_LENGTH }} karakter
                                </span>
                                <span class="text-gray-400">{{ newQuestionForm.question_text.length }} / {{ MAX_LENGTH }}</span>
                            </div>

                            <p v-if="newQuestionForm.errors.question_text" id="new-question-error" class="mt-2 text-xs text-rose-600" role="alert">
                                {{ newQuestionForm.errors.question_text }}
                            </p>

                            <button
                                type="submit"
                                :disabled="newQuestionForm.processing || !canSubmitNew"
                                class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                            >
                                {{ newQuestionForm.processing ? 'Menyimpan…' : 'Tambah Soal' }}
                            </button>
                        </form>

                        <!-- Ringkasan -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-2xl bg-indigo-50 p-4 text-center ring-1 ring-indigo-100">
                                <p class="text-2xl font-bold text-indigo-900">{{ totalCount }}</p>
                                <p class="mt-0.5 text-[11px] font-medium text-indigo-600">Total</p>
                            </div>
                            <div class="rounded-2xl bg-teal-50 p-4 text-center ring-1 ring-teal-100">
                                <p class="text-2xl font-bold text-teal-900">{{ activeTotal }}</p>
                                <p class="mt-0.5 text-[11px] font-medium text-teal-600">Aktif</p>
                            </div>
                            <div class="rounded-2xl bg-amber-50 p-4 text-center ring-1 ring-amber-100">
                                <p class="text-2xl font-bold text-amber-900">{{ inactiveTotal }}</p>
                                <p class="mt-0.5 text-[11px] font-medium text-amber-600">Nonaktif</p>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- ===== KANAN: daftar soal ===== -->
                <section class="lg:col-span-3">
                    <div class="space-y-3">
                        <article
                            v-for="(question, index) in questions"
                            :key="question.id"
                            class="rounded-2xl p-4 ring-1 transition"
                            :class="editingId === question.id
                                ? 'bg-white ring-indigo-300 shadow-md shadow-indigo-500/10'
                                : question.is_active
                                    ? 'bg-white ring-gray-200/80 shadow-sm'
                                    : 'bg-gray-50 ring-gray-200/60'"
                        >
                            <!-- Mode edit -->
                            <div v-if="editingId === question.id">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold text-indigo-600">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100">{{ index + 1 }}</span>
                                    Mengubah soal
                                </div>
                                <textarea
                                    v-model="editText"
                                    rows="3"
                                    :maxlength="MAX_LENGTH"
                                    aria-label="Teks soal"
                                    class="w-full resize-none rounded-xl border-gray-200 bg-gray-50 text-sm focus:border-indigo-400 focus:bg-white focus:ring-indigo-400"
                                ></textarea>
                                <div class="mt-1 flex items-center justify-between text-xs">
                                    <p v-if="editError" class="text-rose-600" role="alert">{{ editError }}</p>
                                    <span v-else></span>
                                    <span class="text-gray-400">{{ editText.length }} / {{ MAX_LENGTH }}</span>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <button
                                        type="button"
                                        @click="saveEdit(question)"
                                        :disabled="editSaving"
                                        class="rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:opacity-40"
                                    >
                                        {{ editSaving ? 'Menyimpan…' : 'Simpan' }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        :disabled="editSaving"
                                        class="rounded-lg bg-gray-100 px-3.5 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 disabled:opacity-40"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>

                            <!-- Mode lihat -->
                            <div v-else class="flex items-start gap-3">
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                    :class="question.is_active ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-200 text-gray-500'"
                                >
                                    {{ index + 1 }}
                                </span>

                                <div class="min-w-0 flex-1" :class="{ 'opacity-60': !question.is_active }">
                                    <p class="text-sm leading-relaxed text-gray-800">{{ question.question_text }}</p>
                                    <span
                                        class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset"
                                        :class="question.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                                            : 'bg-gray-100 text-gray-500 ring-gray-500/20'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="question.is_active ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                        {{ question.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>

                                <!-- Aksi -->
                                <div class="flex shrink-0 items-center gap-1">
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="!!question.is_active"
                                        :aria-label="question.is_active ? 'Nonaktifkan soal' : 'Aktifkan soal'"
                                        :title="togglingId === question.id ? 'Memproses…' : (question.is_active ? 'Nonaktifkan' : 'Aktifkan')"
                                        @click="toggleActive(question)"
                                        :disabled="rowActionRunning"
                                        class="relative mr-1 inline-flex h-6 w-11 shrink-0 items-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 focus-visible:ring-offset-2 disabled:opacity-40 motion-reduce:transition-none"
                                        :class="question.is_active ? 'bg-teal-500' : 'bg-gray-300'"
                                    >
                                        <span
                                            class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition motion-reduce:transition-none"
                                            :class="question.is_active ? 'translate-x-[22px]' : 'translate-x-0.5'"
                                        ></span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="startEdit(question)"
                                        :disabled="rowActionRunning"
                                        title="Edit"
                                        aria-label="Edit soal"
                                        class="rounded-lg p-2 text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 disabled:opacity-40"
                                    >
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </button>

                                    <button
                                        type="button"
                                        @click="destroyQuestion(question)"
                                        :disabled="rowActionRunning"
                                        :title="destroyingId === question.id ? 'Menghapus…' : 'Hapus'"
                                        aria-label="Hapus soal"
                                        class="rounded-lg p-2 text-gray-400 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 disabled:opacity-40"
                                    >
                                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="questions.length === 0"
                        class="flex flex-col items-center rounded-3xl border border-dashed border-gray-300 bg-white py-14 text-center"
                    >
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-indigo-400">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg>
                        </span>
                        <p class="mt-3 text-sm text-gray-500">Belum ada soal untuk sub-kriteria ini.</p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

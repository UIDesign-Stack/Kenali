<script setup>
import { ref } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    subCriteria: { type: Object, required: true },
    questions: { type: Array, required: true },
});

// Form tambah soal baru
const newQuestionForm = useForm({
    question_text: '',
});

function submitNewQuestion() {
    newQuestionForm.post(route('admin.questions.store', props.subCriteria.id), {
        preserveScroll: true,
        onSuccess: () => newQuestionForm.reset(),
    });
}

// State untuk edit inline
const editingId = ref(null);
const editText = ref('');

function startEdit(question) {
    editingId.value = question.id;
    editText.value = question.question_text;
}

function cancelEdit() {
    editingId.value = null;
    editText.value = '';
}

function saveEdit(question) {
    router.patch(route('admin.questions.update', question.id), {
        question_text: editText.value,
    }, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
}

function toggleActive(question) {
    router.patch(route('admin.questions.toggle-active', question.id), {}, {
        preserveScroll: true,
    });
}

function destroyQuestion(question) {
    if (! confirm('Yakin ingin menghapus soal ini?')) return;

    router.delete(route('admin.questions.destroy', question.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.questions.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Bank Soal
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Soal — {{ subCriteria.name }}
                </h1>
            </div>
        </template>

        <div class="max-w-3xl mx-auto p-6">
            <!-- Form tambah soal -->
            <form @submit.prevent="submitNewQuestion" class="mb-8 p-4 border border-gray-200 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tambah soal baru
                </label>
                <textarea
                    v-model="newQuestionForm.question_text"
                    rows="2"
                    placeholder="Contoh: Saya senang menganalisis data untuk menemukan pola tertentu."
                    class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                ></textarea>
                <p v-if="newQuestionForm.errors.question_text" class="text-xs text-red-600 mt-1">
                    {{ newQuestionForm.errors.question_text }}
                </p>
                <button
                    type="submit"
                    :disabled="newQuestionForm.processing || !newQuestionForm.question_text.trim()"
                    class="mt-3 px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    Tambah Soal
                </button>
            </form>

            <!-- Daftar soal -->
            <div class="space-y-3">
                <div
                    v-for="(question, index) in questions"
                    :key="question.id"
                    class="p-4 border border-gray-200 rounded-lg"
                    :class="{ 'opacity-50 bg-gray-50': !question.is_active }"
                >
                    <div v-if="editingId === question.id">
                        <textarea
                            v-model="editText"
                            rows="2"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                        ></textarea>
                        <div class="flex gap-2 mt-2">
                            <button
                                @click="saveEdit(question)"
                                class="px-3 py-1.5 rounded-md bg-teal-600 text-white text-xs font-medium hover:bg-teal-700"
                            >
                                Simpan
                            </button>
                            <button
                                @click="cancelEdit"
                                class="px-3 py-1.5 rounded-md bg-gray-100 text-gray-600 text-xs font-medium hover:bg-gray-200"
                            >
                                Batal
                            </button>
                        </div>
                    </div>

                    <div v-else class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <span class="text-xs text-gray-400">Soal #{{ index + 1 }}</span>
                            <p class="text-sm text-gray-700 mt-0.5">{{ question.question_text }}</p>
                            <span
                                class="inline-block mt-2 text-[10px] px-2 py-0.5 rounded-full"
                                :class="question.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500'"
                            >
                                {{ question.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <div class="flex gap-2 shrink-0">
                            <button
                                @click="startEdit(question)"
                                class="text-xs text-gray-500 hover:text-teal-600"
                            >
                                Edit
                            </button>
                            <button
                                @click="toggleActive(question)"
                                class="text-xs text-gray-500 hover:text-amber-600"
                            >
                                {{ question.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                            <button
                                @click="destroyQuestion(question)"
                                class="text-xs text-gray-500 hover:text-red-600"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="questions.length === 0" class="text-sm text-gray-400 text-center py-8">
                    Belum ada soal untuk sub-kriteria ini.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
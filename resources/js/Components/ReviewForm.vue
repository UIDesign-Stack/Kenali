<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import StarRating from '@/Components/StarRating.vue';

const props = defineProps({
    consultationId: { type: Number, required: true },
    review: { type: Object, default: null },
    editable: { type: Boolean, default: false },
});

const form = useForm({
    rating: props.review?.rating ?? 0,
    comment: props.review?.comment ?? '',
});

const isEdit = computed(() => !!props.review);
const editing = ref(false);
const showForm = computed(() => !isEdit.value || editing.value);
const canSubmit = computed(() => form.rating >= 1 && !form.processing);

// Saat ulasan tersimpan/berubah dari server, tutup mode ubah dan sinkronkan nilai
watch(() => props.review, (r) => {
    editing.value = false;
    form.rating = r?.rating ?? 0;
    form.comment = r?.comment ?? '';
    form.clearErrors();
});

function startEdit() {
    form.rating = props.review.rating;
    form.comment = props.review.comment ?? '';
    editing.value = true;
}

function cancelEdit() {
    editing.value = false;
    form.clearErrors();
}

function submit() {
    if (!canSubmit.value) return;

    const options = { preserveScroll: true };
    if (isEdit.value) {
        form.patch(route('reviews.update', props.review.id), options);
    } else {
        form.post(route('consultations.review.store', props.consultationId), options);
    }
}
</script>

<template>
    <div class="p-4 border border-gray-200 rounded-lg">
        <h3 class="text-sm font-semibold text-gray-800 mb-1">
            {{ isEdit ? 'Ulasanmu' : 'Beri ulasan untuk psikolog' }}
        </h3>

        <!-- Tampilan setelah terkirim -->
        <div v-if="!showForm" class="mt-2">
            <StarRating :model-value="review.rating" readonly />
            <p v-if="review.comment" class="text-sm text-gray-600 mt-2 whitespace-pre-line">{{ review.comment }}</p>

            <div v-if="editable" class="mt-3 flex items-center gap-3">
                <button type="button" @click="startEdit" class="text-sm text-teal-600 font-medium hover:underline">
                    Ubah ulasan
                </button>
                <span class="text-[11px] text-gray-400">Bisa diubah dalam 24 jam sejak dikirim.</span>
            </div>
            <p v-else class="text-[11px] text-gray-400 mt-2">Ulasan tidak bisa diubah lagi setelah 24 jam.</p>
        </div>

        <!-- Form kirim / ubah -->
        <form v-else @submit.prevent="submit" class="mt-2 space-y-3">
            <StarRating v-model="form.rating" />
            <p v-if="form.errors.rating" class="text-xs text-red-600">{{ form.errors.rating }}</p>

            <div>
                <textarea
                    v-model="form.comment"
                    rows="3"
                    maxlength="500"
                    placeholder="Komentar (opsional)"
                    class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                ></textarea>
                <div class="flex justify-between mt-1">
                    <p v-if="form.errors.comment" class="text-xs text-red-600">{{ form.errors.comment }}</p>
                    <p class="text-[10px] text-gray-400 ml-auto">{{ form.comment.length }}/500</p>
                </div>
            </div>

            <p class="text-[11px] text-amber-700 bg-amber-50 rounded-md px-3 py-2">
                Ulasan tampil ke pengguna lain tanpa namamu. Jangan menulis data pribadi atau isi sesi konsultasi.
            </p>

            <p v-if="$page.props.errors?.review" class="text-xs text-red-600" role="alert">
                {{ $page.props.errors.review }}
            </p>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="!canSubmit"
                    class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    {{ form.processing ? 'Menyimpan…' : (isEdit ? 'Simpan Perubahan' : 'Kirim Ulasan') }}
                </button>
                <button v-if="isEdit" type="button" @click="cancelEdit" class="text-sm text-gray-500 hover:text-gray-700">
                    Batal
                </button>
            </div>
        </form>
    </div>
</template>
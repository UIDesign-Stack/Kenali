<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import StarRating from '@/Components/StarRating.vue';

const props = defineProps({
    review: { type: Object, required: true },
    editable: { type: Boolean, default: false },
});

const form = useForm({ reply: props.review.reply ?? '' });
const editing = ref(false);
const hasReply = computed(() => !!props.review.reply);
const showForm = computed(() => (!hasReply.value && props.editable) || editing.value);

watch(() => props.review, (r) => {
    editing.value = false;
    form.reply = r.reply ?? '';
    form.clearErrors();
});

function submit() {
    if (form.processing || form.reply.trim().length < 3) return;
    form.patch(route('psikolog.reviews.reply', props.review.id), { preserveScroll: true });
}
</script>

<template>
    <div class="p-4 border border-gray-200 rounded-lg">
        <h3 class="text-sm font-semibold text-gray-800 mb-2">Ulasan dari pasien</h3>

        <div v-if="review.is_hidden" class="text-xs text-gray-500 bg-gray-50 rounded-md px-3 py-2">
            Ulasan ini disembunyikan oleh admin.
        </div>

        <template v-else>
            <StarRating :model-value="review.rating" readonly />
            <p v-if="review.comment" class="text-sm text-gray-600 mt-2 whitespace-pre-line">{{ review.comment }}</p>
            <p v-else class="text-xs text-gray-400 mt-2">Tanpa komentar.</p>

            <div class="mt-4 pt-3 border-t border-gray-100">
                <div v-if="hasReply && !editing && !review.reply_hidden">
                    <p class="text-xs font-medium text-gray-500 mb-1">Balasanmu</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ review.reply }}</p>
                    <button v-if="editable" type="button" @click="editing = true" class="mt-2 text-sm text-teal-600 hover:underline">
                        Ubah balasan
                    </button>
                </div>

                <p v-else-if="hasReply && review.reply_hidden" class="text-xs text-gray-500">
                    Balasanmu disembunyikan oleh admin.
                </p>

                <form v-else-if="showForm" @submit.prevent="submit" class="space-y-2">
                    <textarea
                        v-model="form.reply"
                        rows="3"
                        maxlength="500"
                        placeholder="Tulis balasan singkat dan profesional"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    ></textarea>
                    <p v-if="form.errors.reply" class="text-xs text-red-600">{{ form.errors.reply }}</p>
                    <p v-if="$page.props.errors?.review" class="text-xs text-red-600" role="alert">{{ $page.props.errors.review }}</p>
                    <p class="text-[11px] text-amber-700 bg-amber-50 rounded-md px-3 py-2">
                        Balasan tampil ke publik. Jangan menyebut detail atau isi sesi konsultasi.
                    </p>
                    <div class="flex items-center gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing || form.reply.trim().length < 3"
                            class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                        >
                            {{ form.processing ? 'Menyimpan…' : 'Kirim Balasan' }}
                        </button>
                        <button v-if="editing" type="button" @click="editing = false" class="text-sm text-gray-500">Batal</button>
                    </div>
                </form>

                <p v-else class="text-xs text-gray-400">Batas waktu membalas ulasan ini sudah lewat.</p>
            </div>
        </template>
    </div>
</template>
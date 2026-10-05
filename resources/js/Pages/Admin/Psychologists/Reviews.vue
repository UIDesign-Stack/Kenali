<script setup>
import { router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StarRating from '@/Components/StarRating.vue';

defineProps({
    psychologistProfile: { type: Object, required: true },
    reviews: { type: Array, required: true },
});

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

function toggleHidden(review) {
    const msg = review.is_hidden
        ? 'Tampilkan kembali ulasan ini?'
        : 'Sembunyikan ulasan ini? Rating psikolog akan dihitung ulang.';
    if (!confirm(msg)) return;
    router.patch(route('admin.reviews.toggle-hidden', review.id), {}, { preserveScroll: true });
}

function toggleReplyHidden(review) {
    router.patch(route('admin.reviews.toggle-reply-hidden', review.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Ulasan — ${psychologistProfile.user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.psychologists.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Psikolog
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Ulasan — {{ psychologistProfile.user.name }}</h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <div v-if="$page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ $page.props.flash.success }}
            </div>

            <p class="mb-4 text-sm text-gray-500">
                Rata-rata (tanpa ulasan tersembunyi):
                <strong>{{ psychologistProfile.rating_count > 0 ? Number(psychologistProfile.rating_avg).toFixed(2) : '–' }}</strong>
                dari {{ psychologistProfile.rating_count }} ulasan.
            </p>

            <div class="space-y-3">
                <div
                    v-for="r in reviews"
                    :key="r.id"
                    class="p-4 border border-gray-200 rounded-lg"
                    :class="{ 'bg-gray-50 opacity-70': r.is_hidden }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <StarRating :model-value="r.rating" readonly size="text-sm" />
                            <span class="text-xs text-gray-600">{{ r.user?.name }}</span>
                            <span v-if="r.is_hidden" class="text-[10px] px-2 py-0.5 rounded-full bg-gray-200 text-gray-600">
                                Disembunyikan
                            </span>
                        </div>
                        <span class="text-[11px] text-gray-400">{{ formatDate(r.created_at) }}</span>
                    </div>

                    <p v-if="r.comment" class="text-sm text-gray-700 mt-2 whitespace-pre-line">{{ r.comment }}</p>

                    <div v-if="r.reply" class="mt-3 ml-4 pl-3 border-l-2 border-teal-200">
                        <p class="text-xs font-medium text-teal-700 mb-0.5">
                            Balasan psikolog
                            <span v-if="r.reply_hidden" class="text-gray-400">(disembunyikan)</span>
                        </p>
                        <p class="text-sm text-gray-600 whitespace-pre-line">{{ r.reply }}</p>
                        <button type="button" @click="toggleReplyHidden(r)" class="mt-1 text-xs text-gray-500 hover:text-amber-600">
                            {{ r.reply_hidden ? 'Tampilkan balasan' : 'Sembunyikan balasan' }}
                        </button>
                    </div>

                    <button type="button" @click="toggleHidden(r)" class="mt-3 text-xs text-gray-500 hover:text-red-600">
                        {{ r.is_hidden ? 'Tampilkan ulasan' : 'Sembunyikan ulasan' }}
                    </button>
                </div>

                <p v-if="reviews.length === 0" class="text-sm text-gray-400 text-center py-8">
                    Belum ada ulasan.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
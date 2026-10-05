<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StarRating from '@/Components/StarRating.vue';

defineProps({
    psychologist: { type: Object, required: true },
    reviews: { type: Array, required: true },
});

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
}
</script>

<template>
    <Head :title="`Ulasan — ${psychologist.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Ulasan — {{ psychologist.name }}</h1>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <div class="mb-6 p-4 border border-gray-200 rounded-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ psychologist.name }}</p>
                    <p class="text-xs text-gray-500">{{ psychologist.specialization }}</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-semibold text-gray-800">
                        {{ psychologist.rating_count > 0 ? Number(psychologist.rating_avg).toFixed(1) : '–' }}
                    </p>
                    <StarRating :model-value="Number(psychologist.rating_avg)" readonly size="text-sm" />
                    <p class="text-[11px] text-gray-400">{{ psychologist.rating_count }} ulasan</p>
                </div>
            </div>

            <div class="space-y-3">
                <div v-for="r in reviews" :key="r.id" class="p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <StarRating :model-value="r.rating" readonly size="text-sm" />
                            <span class="text-xs text-gray-500">Pengguna</span>
                        </div>
                        <span class="text-[11px] text-gray-400">{{ formatDate(r.created_at) }}</span>
                    </div>

                    <p v-if="r.comment" class="text-sm text-gray-700 mt-2 whitespace-pre-line">{{ r.comment }}</p>

                    <div v-if="r.reply" class="mt-3 ml-4 pl-3 border-l-2 border-teal-200">
                        <p class="text-xs font-medium text-teal-700 mb-0.5">Balasan psikolog</p>
                        <p class="text-sm text-gray-600 whitespace-pre-line">{{ r.reply }}</p>
                    </div>
                </div>

                <p v-if="reviews.length === 0" class="text-sm text-gray-400 text-center py-8">
                    Belum ada ulasan.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
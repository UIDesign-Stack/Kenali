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

const avatarColors = [
    'bg-indigo-100 text-indigo-700',
    'bg-teal-100 text-teal-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-sky-100 text-sky-700',
    'bg-violet-100 text-violet-700',
];

function avatarColor(index) {
    return avatarColors[index % avatarColors.length];
}

function initials(name) {
    if (!name) return '?';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() ?? '')
        .join('');
}
</script>

<template>
    <Head :title="`Ulasan — ${psychologist.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Ulasan — {{ psychologist.name }}</h1>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

                <!-- ===== Kiri: profil & rating ===== -->
                <aside class="lg:col-span-2">
                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-800 via-teal-600 to-sky-600 p-6 text-white shadow-xl shadow-teal-900/20 sm:p-8 lg:sticky lg:top-24">
                        <div class="pointer-events-none absolute -top-14 -right-10 h-48 w-48 rounded-full bg-amber-300/30 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-16 left-6 h-48 w-48 rounded-full bg-indigo-400/30 blur-3xl"></div>

                        <div class="relative">
                            <div class="flex items-center gap-4">
                                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-xl font-bold ring-1 ring-white/30 backdrop-blur">
                                    {{ initials(psychologist.name) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-lg font-bold">{{ psychologist.name }}</p>
                                    <p class="text-sm text-teal-50">{{ psychologist.specialization }}</p>
                                </div>
                            </div>

                            <div class="mt-8 flex items-end gap-4">
                                <p class="text-6xl font-bold leading-none">
                                    {{ psychologist.rating_count > 0 ? Number(psychologist.rating_avg).toFixed(1) : '–' }}
                                </p>
                                <div class="pb-1">
                                    <div class="rounded-lg bg-white/15 px-2 py-1 ring-1 ring-white/25 backdrop-blur">
                                        <StarRating :model-value="Number(psychologist.rating_avg)" readonly size="text-sm" />
                                    </div>
                                    <p class="mt-1.5 text-xs text-teal-50">{{ psychologist.rating_count }} ulasan</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- ===== Kanan: daftar ulasan ===== -->
                <section class="lg:col-span-3">
                    <div class="space-y-4">
                        <article
                            v-for="(r, index) in reviews"
                            :key="r.id"
                            class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-gray-200/80 sm:p-6"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="avatarColor(index)">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                    </span>
                                    <div>
                                        <span class="block text-sm font-medium text-gray-700">Pengguna</span>
                                        <StarRating :model-value="r.rating" readonly size="text-sm" />
                                    </div>
                                </div>
                                <span class="shrink-0 text-[11px] text-gray-400">{{ formatDate(r.created_at) }}</span>
                            </div>

                            <p v-if="r.comment" class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ r.comment }}</p>

                            <div v-if="r.reply" class="mt-4 rounded-2xl border-l-4 border-teal-400 bg-teal-50/70 p-4">
                                <p class="mb-1 text-xs font-semibold text-teal-700">Balasan psikolog</p>
                                <p class="whitespace-pre-line text-sm leading-relaxed text-gray-600">{{ r.reply }}</p>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="reviews.length === 0"
                        class="flex flex-col items-center rounded-3xl border border-dashed border-gray-300 bg-white py-14 text-center"
                    >
                        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-2xl text-amber-400">★</span>
                        <p class="mt-3 text-sm text-gray-500">Belum ada ulasan.</p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

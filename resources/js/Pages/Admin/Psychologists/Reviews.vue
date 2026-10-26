<script setup>
import { ref, computed } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StarRating from '@/Components/StarRating.vue';

const props = defineProps({
    psychologistProfile: { type: Object, required: true },
    reviews: { type: Array, required: true },
});

const processingId = ref(null);
const processingAction = ref(null);

function isBusy(review) {
    return processingId.value === review.id;
}

function isProcessing(review, action) {
    return isBusy(review) && processingAction.value === action;
}

function finishProcessing() {
    processingId.value = null;
    processingAction.value = null;
}

function run(review, action, url) {
    processingId.value = review.id;
    processingAction.value = action;

    router.patch(url, {}, {
        preserveScroll: true,
        onFinish: finishProcessing,
    });
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

function toggleHidden(review) {
    if (isBusy(review)) return;

    const msg = review.is_hidden
        ? 'Tampilkan kembali ulasan ini?'
        : 'Sembunyikan ulasan ini? Rating psikolog akan dihitung ulang.';
    if (!confirm(msg)) return;

    run(review, 'review', route('admin.reviews.toggle-hidden', review.id));
}

function toggleReplyHidden(review) {
    if (isBusy(review)) return;

    const msg = review.reply_hidden
        ? 'Tampilkan kembali balasan psikolog ini?'
        : 'Sembunyikan balasan psikolog ini?';
    if (!confirm(msg)) return;

    run(review, 'reply', route('admin.reviews.toggle-reply-hidden', review.id));
}

const visibleReviews = computed(() => props.reviews.filter((r) => !r.is_hidden));

const distribution = computed(() => {
    const total = visibleReviews.value.length;

    return [5, 4, 3, 2, 1].map((star) => {
        const count = visibleReviews.value.filter((r) => Number(r.rating) === star).length;
        return { star, count, percent: total ? Math.round((count / total) * 100) : 0 };
    });
});

const hiddenCount = computed(() => props.reviews.filter((r) => r.is_hidden).length);

const barColors = {
    5: 'bg-emerald-500',
    4: 'bg-teal-400',
    3: 'bg-amber-400',
    2: 'bg-orange-400',
    1: 'bg-rose-400',
};

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
    <Head :title="`Ulasan — ${psychologistProfile.user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('admin.psychologists.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← Psikolog
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Ulasan
                    <span class="ml-1 inline-flex items-center rounded-lg bg-gradient-to-r from-indigo-100 to-violet-100 px-2.5 py-0.5 text-indigo-700">{{ psychologistProfile.user.name }}</span>
                </h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
            <div
                v-if="$page.props.flash?.success"
                class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200"
                role="status"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </span>
                {{ $page.props.flash.success }}
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

                <!-- ===== Kiri: ringkasan rating ===== -->
                <aside class="lg:col-span-2">
                    <div class="space-y-4 lg:sticky lg:top-24">
                        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-800 via-teal-600 to-sky-600 p-6 text-white shadow-xl shadow-teal-900/20 sm:p-8">
                            <div class="pointer-events-none absolute -top-14 -right-10 h-48 w-48 rounded-full bg-amber-300/30 blur-3xl"></div>
                            <div class="pointer-events-none absolute -bottom-16 left-6 h-48 w-48 rounded-full bg-indigo-400/30 blur-3xl"></div>

                            <div class="relative">
                                <p class="text-sm text-teal-50">Rata-rata (tanpa ulasan tersembunyi)</p>
                                <div class="mt-2 flex items-end gap-3">
                                    <span class="text-5xl font-bold leading-none">
                                        {{ psychologistProfile.rating_count > 0 ? Number(psychologistProfile.rating_avg).toFixed(2) : '–' }}
                                    </span>
                                    <span class="pb-1 text-2xl text-amber-300">★</span>
                                </div>
                                <p class="mt-2 text-sm text-teal-50">dari {{ psychologistProfile.rating_count }} ulasan</p>
                            </div>
                        </div>

                        <div class="rounded-3xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm sm:p-6">
                            <h2 class="text-sm font-semibold text-gray-800">Sebaran rating</h2>
                            <ul class="mt-4 space-y-3">
                                <li v-for="d in distribution" :key="d.star" class="flex items-center gap-3 text-xs">
                                    <span class="w-6 shrink-0 font-semibold text-gray-600">{{ d.star }}★</span>
                                    <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full" :class="barColors[d.star]" :style="{ width: d.percent + '%' }"></div>
                                    </div>
                                    <span class="w-6 shrink-0 text-right text-gray-400">{{ d.count }}</span>
                                </li>
                            </ul>
                            <p v-if="hiddenCount > 0" class="mt-4 rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-500">
                                {{ hiddenCount }} ulasan disembunyikan dan tidak dihitung.
                            </p>
                        </div>
                    </div>
                </aside>

                <!-- ===== Kanan: daftar ulasan ===== -->
                <section class="lg:col-span-3">
                    <div class="space-y-4">
                        <article
                            v-for="(r, index) in reviews"
                            :key="r.id"
                            class="rounded-3xl p-5 ring-1 transition sm:p-6"
                            :class="r.is_hidden ? 'bg-gray-50 ring-gray-200/60' : 'bg-white shadow-sm ring-gray-200/80'"
                        >
                            <div :class="{ 'opacity-60': r.is_hidden }">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-bold" :class="avatarColor(index)">
                                            {{ initials(r.user?.name) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-gray-800">{{ r.user?.name }}</p>
                                            <div class="mt-0.5 flex items-center gap-2">
                                                <StarRating :model-value="r.rating" readonly size="text-sm" />
                                                <span
                                                    v-if="r.is_hidden"
                                                    class="rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-medium text-gray-600"
                                                >
                                                    Disembunyikan
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="shrink-0 text-[11px] text-gray-400">{{ formatDate(r.created_at) }}</span>
                                </div>

                                <p v-if="r.comment" class="mt-3 whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ r.comment }}</p>

                                <div v-if="r.reply" class="mt-4 rounded-2xl border-l-4 border-teal-400 bg-teal-50/70 p-4">
                                    <p class="mb-1 text-xs font-semibold text-teal-700">
                                        Balasan psikolog
                                        <span v-if="r.reply_hidden" class="font-normal text-gray-400">(disembunyikan)</span>
                                    </p>
                                    <p class="whitespace-pre-line text-sm leading-relaxed text-gray-600">{{ r.reply }}</p>
                                    <button
                                        type="button"
                                        @click="toggleReplyHidden(r)"
                                        :disabled="isBusy(r)"
                                        class="mt-2 rounded-lg px-2.5 py-1 text-xs font-medium text-teal-700 transition hover:bg-teal-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-400 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {{ isProcessing(r, 'reply') ? '...' : (r.reply_hidden ? 'Tampilkan balasan' : 'Sembunyikan balasan') }}
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end border-t border-gray-100 pt-3">
                                <button
                                    type="button"
                                    @click="toggleHidden(r)"
                                    :disabled="isBusy(r)"
                                    class="rounded-xl px-3.5 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="r.is_hidden
                                        ? 'bg-teal-50 text-teal-700 hover:bg-teal-100 focus-visible:ring-teal-400'
                                        : 'bg-rose-50 text-rose-600 hover:bg-rose-100 focus-visible:ring-rose-400'"
                                >
                                    {{ isProcessing(r, 'review') ? '...' : (r.is_hidden ? 'Tampilkan ulasan' : 'Sembunyikan ulasan') }}
                                </button>
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

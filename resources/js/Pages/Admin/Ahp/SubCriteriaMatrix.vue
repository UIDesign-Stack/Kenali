<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AhpMatrixInput from '@/Components/AhpMatrixInput.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    criteria: { type: Object, required: true },
    items: { type: Array, required: true },
});

const hasSaved = ref(false);

function handleSaved() {
    hasSaved.value = true;
}
</script>

<template>
    <Head :title="`Bobot AHP — Sub-kriteria ${criteria.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('admin.ahp.criteria.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← Kriteria Utama
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Bobot AHP — Sub-kriteria
                    <span class="ml-1 inline-flex items-center rounded-lg bg-gradient-to-r from-indigo-100 to-violet-100 px-2.5 py-0.5 text-indigo-700">{{ criteria.name }}</span>
                </h1>
            </div>
        </template>

        <!-- Peringatan: sub-kriteria kurang dari 2 -->
        <div v-if="items.length < 2" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <p>
                    Minimal butuh 2 sub-kriteria di dalam "{{ criteria.name }}" untuk membuat
                    matriks perbandingan. Saat ini baru ada {{ items.length }} sub-kriteria terdaftar.
                </p>
            </div>
        </div>

        <AhpMatrixInput
            v-else
            :items="items"
            :submit-url="route('admin.ahp.sub-criteria.store', criteria.id)"
            id-field="sub_criteria_ids"
            @saved="handleSaved"
        />

        <!-- Notifikasi tersimpan -->
        <div v-if="hasSaved" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <div
                class="flex flex-col gap-4 rounded-3xl bg-emerald-50 p-5 ring-1 ring-emerald-200 sm:flex-row sm:items-center sm:justify-between sm:p-6"
                role="status"
            >
                <div class="flex items-center gap-3 text-sm text-emerald-800">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                    <span>Bobot sub-kriteria "{{ criteria.name }}" tersimpan.</span>
                </div>
                <Link
                    :href="route('admin.ahp.criteria.index')"
                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/25 transition hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2"
                >
                    Kembali ke daftar kriteria
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AlternativeForm from '@/Components/AlternativeForm.vue';

const props = defineProps({
    alternative: { type: Object, required: true },
    lifePhaseOptions: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head :title="`Edit — ${alternative.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('admin.alternatives.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← Alternatif
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Edit
                    <span class="ml-1 inline-flex items-center rounded-lg bg-gradient-to-r from-indigo-100 to-violet-100 px-2.5 py-0.5 text-indigo-700">{{ alternative.name }}</span>
                </h1>
            </div>
        </template>

        <AlternativeForm
            :initial="alternative"
            :life-phase-options="lifePhaseOptions"
            method="put"
            :url="route('admin.alternatives.update', alternative.id)"
            submit-label="Simpan Perubahan"
        />
    </AuthenticatedLayout>
</template>
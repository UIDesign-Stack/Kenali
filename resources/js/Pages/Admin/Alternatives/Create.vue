<script setup>
import { Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AlternativeForm from '@/Components/AlternativeForm.vue';

defineProps({
    lifePhaseOptions: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Head title="Tambah Alternatif" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('admin.alternatives.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← Alternatif
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Tambah Alternatif</h1>
            </div>
        </template>

        <AlternativeForm
            :life-phase-options="lifePhaseOptions"
            method="post"
            :url="route('admin.alternatives.store')"
            submit-label="Simpan"
        />
    </AuthenticatedLayout>
</template>
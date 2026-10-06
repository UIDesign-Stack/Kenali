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
            <div class="flex items-center gap-3">
                <Link :href="route('admin.alternatives.index')" class="text-sm text-gray-400 hover:text-gray-600">
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

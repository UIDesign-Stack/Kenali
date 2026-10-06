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
            <div class="flex items-center gap-3">
                <Link :href="route('admin.alternatives.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Alternatif
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Edit — {{ alternative.name }}</h1>
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

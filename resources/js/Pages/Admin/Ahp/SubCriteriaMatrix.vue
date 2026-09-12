<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AhpMatrixInput from '@/Components/AhpMatrixInput.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    criteria: { type: Object, required: true },
    items: { type: Array, required: true },
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('admin.ahp.criteria.index')"
                    class="text-sm text-gray-400 hover:text-gray-600"
                >
                    ← Kriteria Utama
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Bobot AHP — Sub-kriteria {{ criteria.name }}
                </h1>
            </div>
        </template>

        <AhpMatrixInput
            :items="items"
            :submit-url="`/admin/ahp/criteria/${criteria.id}/sub-criteria`"
            id-field="sub_criteria_ids"
        />
    </AuthenticatedLayout>
</template>
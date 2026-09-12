<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AhpMatrixInput from '@/Components/AhpMatrixInput.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    items: { type: Array, required: true },
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">
                Bobot AHP — Kriteria Utama
            </h1>
        </template>

        <AhpMatrixInput
            :items="items"
            submit-url="/admin/ahp/criteria"
            id-field="criteria_ids"
        />

        <div class="max-w-3xl mx-auto px-6 pb-10">
            <div class="border-t border-gray-200 pt-6 mt-2">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    Lanjut atur bobot sub-kriteria
                </h3>
                <p class="text-xs text-gray-500 mb-4">
                    Setelah bobot kriteria utama tersimpan konsisten, atur juga bobot
                    di dalam masing-masing kriteria berikut.
                </p>

                <div class="space-y-2">
                    <Link
                        v-for="item in items"
                        :key="item.id"
                        :href="route('admin.ahp.sub-criteria.index', item.id)"
                        class="flex items-center justify-between p-3 border border-gray-200 rounded-md hover:border-teal-400 hover:bg-teal-50 transition"
                    >
                        <span class="text-sm font-medium text-gray-700">{{ item.name }}</span>
                        <span class="text-xs text-teal-600">Atur Sub-kriteria →</span>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
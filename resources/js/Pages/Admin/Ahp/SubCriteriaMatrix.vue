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

        <div v-if="items.length < 2" class="max-w-3xl mx-auto px-6 pt-6">
            <div class="p-4 rounded-md bg-amber-50 text-amber-700 text-sm">
                Minimal butuh 2 sub-kriteria di dalam "{{ criteria.name }}" untuk membuat
                matriks perbandingan. Saat ini baru ada {{ items.length }} sub-kriteria terdaftar.
            </div>
        </div>

        <AhpMatrixInput
            v-else
            :items="items"
            :submit-url="route('admin.ahp.sub-criteria.store', criteria.id)"
            id-field="sub_criteria_ids"
            @saved="handleSaved"
        />

        <div v-if="hasSaved" class="max-w-3xl mx-auto px-6 pb-10">
            <div class="p-3 rounded-md bg-teal-50 text-teal-700 text-sm flex items-center justify-between gap-3">
                <span class="flex items-center gap-2">
                    <span>✓</span>
                    <span>Bobot sub-kriteria "{{ criteria.name }}" tersimpan.</span>
                </span>
                <Link
                    :href="route('admin.ahp.criteria.index')"
                    class="text-teal-700 underline underline-offset-2 whitespace-nowrap"
                >
                    Kembali ke daftar kriteria
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
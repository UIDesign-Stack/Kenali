<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AhpMatrixInput from '@/Components/AhpMatrixInput.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, required: true },
});

const hasSaved = ref(false);

function handleSaved() {
    hasSaved.value = true;
}
</script>

<template>
    <Head title="Bobot AHP — Kriteria Utama" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">
                Bobot AHP — Kriteria Utama
            </h1>
        </template>

        <div v-if="items.length < 2" class="max-w-3xl mx-auto px-6 pt-6">
            <div class="p-4 rounded-md bg-amber-50 text-amber-700 text-sm">
                Minimal butuh 2 kriteria utama untuk membuat matriks perbandingan.
                Saat ini baru ada {{ items.length }} kriteria terdaftar.
            </div>
        </div>

        <AhpMatrixInput
            v-else
            :items="items"
            submit-url="/admin/ahp/criteria"
            id-field="criteria_ids"
            @saved="handleSaved"
        />

        <div class="max-w-3xl mx-auto px-6 pb-10">
            <div class="border-t border-gray-200 pt-6 mt-2">
                <div v-if="hasSaved" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm flex items-center gap-2">
                    <span>✓</span>
                    <span>Bobot kriteria utama tersimpan. Lanjutkan atur sub-kriteria di bawah.</span>
                </div>

                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    Lanjut atur bobot sub-kriteria
                </h3>
                <p class="text-xs text-gray-500 mb-4">
                    Setelah bobot kriteria utama tersimpan konsisten, atur juga bobot
                    di dalam masing-masing kriteria berikut.
                </p>

                <div v-if="items.length === 0" class="text-sm text-gray-400 text-center py-6">
                    Belum ada kriteria utama yang terdaftar.
                </div>

                <nav v-else class="space-y-2" aria-label="Daftar kriteria untuk atur sub-kriteria">
                    <Link
                        v-for="item in items"
                        :key="item.id"
                        :href="route('admin.ahp.sub-criteria.index', item.id)"
                        class="flex items-center justify-between p-3 border border-gray-200 rounded-md hover:border-teal-400 hover:bg-teal-50 transition"
                    >
                        <span class="text-sm font-medium text-gray-700">{{ item.name }}</span>
                        <span class="text-xs text-teal-600">Atur Sub-kriteria →</span>
                    </Link>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
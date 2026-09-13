<script setup>
import { ref } from 'vue';
import { router, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    alternatives: { type: Array, required: true },
    lifePhaseOptions: { type: Object, default: () => ({}) },
    totalSubCriteria: { type: Number, default: 0 },
});

const page = usePage();

const processingId = ref(null);
const processingAction = ref(null);

function isProcessing(alt, action) {
    return processingId.value === alt.id && processingAction.value === action;
}

function toggleActive(alternative) {
    processingId.value = alternative.id;
    processingAction.value = 'toggle';

    router.patch(route('admin.alternatives.toggle-active', alternative.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
            processingAction.value = null;
        },
    });
}

function destroyAlternative(alternative) {
    if (! confirm(`Yakin ingin menghapus "${alternative.name}"?`)) return;

    processingId.value = alternative.id;
    processingAction.value = 'destroy';

    router.delete(route('admin.alternatives.destroy', alternative.id), {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
            processingAction.value = null;
        },
    });
}
</script>

<template>
    <Head title="Alternatif Bidang/Karier" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Alternatif Bidang/Karier</h1>
                <Link
                    :href="route('admin.alternatives.create')"
                    class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium hover:bg-teal-700"
                >
                    + Tambah Alternatif
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto p-6">
            <div v-if="page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ page.props.flash.success }}
            </div>

            <div v-if="page.props.errors?.alternative" class="mb-4 p-3 rounded-md bg-red-50 text-red-700 text-sm">
                {{ page.props.errors.alternative }}
            </div>

            <div class="space-y-3">
                <div
                    v-for="alt in alternatives"
                    :key="alt.id"
                    class="p-4 border border-gray-200 rounded-lg"
                    :class="{ 'opacity-50 bg-gray-50': !alt.is_active }"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-gray-800">{{ alt.name }}</h3>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                    {{ lifePhaseOptions[alt.life_phase] ?? alt.life_phase }}
                                </span>
                                <span
                                    class="text-[10px] px-2 py-0.5 rounded-full"
                                    :class="alt.profiles_count >= totalSubCriteria
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-amber-100 text-amber-700'"
                                >
                                    Profil ideal: {{ alt.profiles_count }}/{{ totalSubCriteria }}
                                </span>
                            </div>
                            <p v-if="alt.description" class="text-sm text-gray-500 mt-1">
                                {{ alt.description }}
                            </p>
                        </div>

                        <div class="flex gap-3 shrink-0 text-xs">
                            <Link
                                :href="route('admin.alternative-profiles.edit', alt.id)"
                                class="text-teal-600 hover:text-teal-800 font-medium"
                            >
                                Atur Profil Ideal
                            </Link>
                            <Link
                                :href="route('admin.alternatives.edit', alt.id)"
                                class="text-gray-500 hover:text-gray-700"
                            >
                                Edit
                            </Link>
                            <button
                                @click="toggleActive(alt)"
                                :disabled="isProcessing(alt, 'toggle')"
                                class="text-gray-500 hover:text-amber-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isProcessing(alt, 'toggle') ? '...' : (alt.is_active ? 'Nonaktifkan' : 'Aktifkan') }}
                            </button>
                            <button
                                @click="destroyAlternative(alt)"
                                :disabled="isProcessing(alt, 'destroy')"
                                class="text-gray-500 hover:text-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isProcessing(alt, 'destroy') ? 'Menghapus...' : 'Hapus' }}
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="alternatives.length === 0" class="text-sm text-gray-400 text-center py-8">
                    Belum ada alternatif. Klik "+ Tambah Alternatif" untuk mulai.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
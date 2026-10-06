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

function isBusy(alt) {
    return processingId.value === alt.id;
}

function isProcessing(alt, action) {
    return isBusy(alt) && processingAction.value === action;
}

function finishProcessing() {
    processingId.value = null;
    processingAction.value = null;
}

function toggleActive(alternative) {
    if (isBusy(alternative)) return;

    processingId.value = alternative.id;
    processingAction.value = 'toggle';

    router.patch(route('admin.alternatives.toggle-active', alternative.id), {}, {
        preserveScroll: true,
        onFinish: finishProcessing,
    });
}

function destroyAlternative(alternative) {
    if (isBusy(alternative)) return;
    if (! confirm(`Yakin ingin menghapus "${alternative.name}"?`)) return;

    processingId.value = alternative.id;
    processingAction.value = 'destroy';

    router.delete(route('admin.alternatives.destroy', alternative.id), {
        preserveScroll: true,
        onFinish: finishProcessing,
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

            <div v-if="page.props.errors?.alternative" class="mb-4 p-3 rounded-md bg-red-50 text-red-700 text-sm" role="alert">
                {{ page.props.errors.alternative }}
            </div>

            <div class="space-y-3">
                <div
                    v-for="alt in alternatives"
                    :key="alt.id"
                    class="p-4 border border-gray-200 rounded-lg"
                    :class="{ 'bg-gray-50': !alt.is_active }"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1" :class="{ 'opacity-50': !alt.is_active }">
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
                                type="button"
                                @click="toggleActive(alt)"
                                :disabled="isBusy(alt) || (!alt.is_active && alt.profiles_count < totalSubCriteria)"
                                :title="!alt.is_active && alt.profiles_count < totalSubCriteria ? 'Lengkapi profil ideal dulu' : ''"
                                class="text-gray-500 hover:text-amber-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isProcessing(alt, 'toggle') ? '...' : (alt.is_active ? 'Nonaktifkan' : 'Aktifkan') }}
                            </button>
                            <button
                                type="button"
                                @click="destroyAlternative(alt)"
                                :disabled="isBusy(alt)"
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

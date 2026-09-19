<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, Head } from '@inertiajs/vue3';

defineProps({
    criteria: { type: Array, required: true },
});
</script>

<template>
    <Head title="Bank Soal" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Bank Soal</h1>
        </template>

        <div class="max-w-3xl mx-auto p-6 space-y-8">
            <div v-if="!criteria.length" class="text-sm text-gray-400 text-center py-16">
                Belum ada kriteria yang tersedia.
            </div>

            <!-- v-for dipindah ke <template> terpisah dari v-else, bukan
                 digabung di satu elemen yang sama (Vue style guide
                 menyarankan tidak mencampur v-for dengan direktif
                 kondisional pada node yang sama). -->
            <template v-else>
                <div v-for="crit in criteria" :key="crit.id">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                        {{ crit.name }}
                    </h2>

                    <div v-if="!crit.sub_criteria.length" class="text-xs text-gray-400 italic px-1">
                        Belum ada sub-kriteria.
                    </div>

                    <div v-else class="space-y-2">
                        <Link
                            v-for="sub in crit.sub_criteria"
                            :key="sub.id"
                            :href="route('admin.questions.manage', sub.id)"
                            class="flex items-center justify-between p-4 border border-gray-200 rounded-md hover:border-teal-400 hover:bg-teal-50 transition"
                        >
                            <span class="text-sm font-medium text-gray-700">{{ sub.name }}</span>
                            <span
                                class="text-xs px-2 py-1 rounded-full"
                                :class="sub.questions_count > 0
                                    ? 'bg-teal-100 text-teal-700'
                                    : 'bg-amber-100 text-amber-700'"
                                :aria-label="`${sub.questions_count} soal tersedia`"
                            >
                                {{ sub.questions_count }} soal
                            </span>
                        </Link>
                    </div>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    consultations: { type: Array, required: true },
});

const statusLabel = {
    pending: 'Menunggu respon',
    scheduled: 'Terjadwal',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

const statusColor = {
    pending: 'bg-amber-100 text-amber-700',
    scheduled: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-gray-200 text-gray-500',
};

function typeLabel(type) {
    return type === 'chat' ? 'Chat' : type === 'video_call' ? 'Video Call' : type;
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Konsultasi Saya</h1>
                <Link
                    :href="route('consultations.create')"
                    class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium hover:bg-teal-700"
                >
                    + Ajukan Konsultasi
                </Link>
            </div>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <div v-if="consultations.length === 0" class="text-center py-16">
                <p class="text-sm text-gray-400 mb-4">Belum ada konsultasi.</p>
                <Link :href="route('consultations.create')" class="text-sm text-teal-600 font-medium hover:underline">
                    Ajukan konsultasi pertamamu →
                </Link>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="c in consultations"
                    :key="c.id"
                    :href="route('consultations.show', c.id)"
                    class="block p-4 border border-gray-200 rounded-lg hover:border-teal-300 transition"
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold text-gray-800">
                            {{ c.psychologist_profile?.user?.name ?? 'Psikolog tidak diketahui' }}
                        </span>
                        <span
                            class="text-[10px] px-2 py-0.5 rounded-full"
                            :class="statusColor[c.status] ?? 'bg-gray-100 text-gray-500'"
                        >
                            {{ statusLabel[c.status] ?? c.status }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400">{{ typeLabel(c.type) }}</p>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    consultations: { type: Array, required: true },
});

const statusLabel = {
    pending: 'Menunggu respon Anda',
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
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Konsultasi Masuk</h1>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <div v-if="consultations.length === 0" class="text-center py-16 text-sm text-gray-400">
                Belum ada permintaan konsultasi.
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="c in consultations"
                    :key="c.id"
                    :href="route('psikolog.consultations.show', c.id)"
                    class="block p-4 border border-gray-200 rounded-lg hover:border-teal-300 transition"
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold text-gray-800">{{ c.user.name }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full" :class="statusColor[c.status]">
                            {{ statusLabel[c.status] }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 capitalize">{{ c.type === 'chat' ? 'Chat' : 'Video Call' }}</p>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
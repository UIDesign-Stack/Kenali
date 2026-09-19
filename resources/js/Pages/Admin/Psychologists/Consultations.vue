<script setup>
import { router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    psychologistProfile: { type: Object, required: true },
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

function canForceCancel(consultation) {
    return ['pending', 'scheduled'].includes(consultation.status);
}

function forceCancel(consultation) {
    const reason = prompt(
        'PERINGATAN: Ini akan membatalkan konsultasi ini atas nama admin, bukan psikolog.\n' +
        'Gunakan hanya untuk kondisi darurat (psikolog tidak bisa login sendiri).\n\n' +
        'Masukkan alasan pembatalan:'
    );
    if (!reason) return;

    router.patch(route('admin.consultations.force-cancel', consultation.id), {
        cancelled_reason: reason,
    }, { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.psychologists.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Psikolog
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    Konsultasi — {{ psychologistProfile.user.name }}
                </h1>
            </div>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <div class="mb-4 p-3 rounded-md bg-amber-50 text-xs text-amber-700">
                Gunakan tombol "Batalkan Paksa" hanya untuk kondisi darurat, misalnya
                psikolog tidak bisa login sendiri (sakit mendadak, dll). Alasan wajib
                diisi dan akan tercatat di riwayat konsultasi.
            </div>

            <div v-if="$page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ $page.props.flash.success }}
            </div>

            <div class="space-y-3">
                <div
                    v-for="c in consultations"
                    :key="c.id"
                    class="p-4 border border-gray-200 rounded-lg"
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold text-gray-800">{{ c.user.name }}</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full" :class="statusColor[c.status]">
                            {{ statusLabel[c.status] }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 capitalize mb-2">{{ c.type === 'chat' ? 'Chat' : 'Video Call' }}</p>

                    <button
                        v-if="canForceCancel(c)"
                        @click="forceCancel(c)"
                        class="text-xs text-red-600 hover:text-red-800"
                    >
                        Batalkan Paksa (Darurat)
                    </button>
                    <p v-if="c.status === 'cancelled' && c.cancelled_reason" class="text-xs text-gray-400 mt-1">
                        Alasan: {{ c.cancelled_reason }}
                    </p>
                </div>

                <p v-if="consultations.length === 0" class="text-sm text-gray-400 text-center py-8">
                    Psikolog ini belum punya konsultasi.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    psychologists: { type: Array, required: true },
});

const page = usePage();

// Lacak psikolog mana yang sedang diproses, supaya tombolnya bisa di-disable
// dan mencegah double-click mengirim request dobel.
const processingId = ref(null);
const processingAction = ref(null);

function isProcessing(psych, action) {
    return processingId.value === psych.id && processingAction.value === action;
}

function toggleVerified(psych) {
    const confirmMessage = psych.is_verified
        ? `Batalkan verifikasi untuk ${psych.user.name}?`
        : `Verifikasi ${psych.user.name}?`;

    if (!window.confirm(confirmMessage)) return;

    processingId.value = psych.id;
    processingAction.value = 'verified';

    router.patch(route('admin.psychologists.toggle-verified', psych.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
            processingAction.value = null;
        },
    });
}

function toggleAvailable(psych) {
    // Konfirmasi hanya perlu saat MENONAKTIFKAN, karena ini yang berisiko
    // menyembunyikan psikolog di tengah konsultasi yang sedang berjalan.
    if (psych.is_available) {
        if (!window.confirm(`Nonaktifkan ketersediaan ${psych.user.name}? Psikolog tidak akan bisa menerima konsultasi baru.`)) {
            return;
        }
    }

    processingId.value = psych.id;
    processingAction.value = 'available';

    router.patch(route('admin.psychologists.toggle-available', psych.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
            processingAction.value = null;
        },
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Manajemen Psikolog</h1>
        </template>

        <div class="max-w-3xl mx-auto p-6">
            <div v-if="page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ page.props.flash.success }}
            </div>

            <div v-if="page.props.errors?.psychologist" class="mb-4 p-3 rounded-md bg-red-50 text-red-700 text-sm">
                {{ page.props.errors.psychologist }}
            </div>

            <div class="space-y-3">
                <div
                    v-for="psych in psychologists"
                    :key="psych.id"
                    class="p-4 border border-gray-200 rounded-lg"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="text-sm font-semibold text-gray-800">{{ psych.user.name }}</span>
                                <span
                                    class="text-[10px] px-2 py-0.5 rounded-full"
                                    :class="psych.is_verified ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                >
                                    {{ psych.is_verified ? 'Terverifikasi' : 'Belum diverifikasi' }}
                                </span>
                                <span
                                    v-if="!psych.is_available"
                                    class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500"
                                >
                                    Tidak tersedia
                                </span>
                            </div>
                            <p class="text-xs text-gray-400">{{ psych.user.email }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ psych.specialization }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">No. lisensi: {{ psych.license_number }}</p>
                            <p class="text-xs text-gray-400">{{ psych.consultations_count }} konsultasi ditangani</p>
                        </div>

                        <div class="flex flex-col gap-2 items-end shrink-0">
                            <button
                                type="button"
                                @click="toggleVerified(psych)"
                                :disabled="isProcessing(psych, 'verified')"
                                class="text-xs px-3 py-1.5 rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="psych.is_verified
                                    ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    : 'bg-teal-600 text-white hover:bg-teal-700'"
                            >
                                {{ isProcessing(psych, 'verified') ? 'Memproses...' : (psych.is_verified ? 'Batalkan Verifikasi' : 'Verifikasi') }}
                            </button>
                            <button
                                type="button"
                                @click="toggleAvailable(psych)"
                                :disabled="isProcessing(psych, 'available')"
                                class="text-xs text-gray-500 hover:text-amber-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isProcessing(psych, 'available') ? 'Memproses...' : (psych.is_available ? 'Nonaktifkan ketersediaan' : 'Aktifkan ketersediaan') }}
                            </button>
                        </div>
                    </div>
                </div>

                <p v-if="psychologists.length === 0" class="text-sm text-gray-400 text-center py-8">
                    Belum ada psikolog terdaftar.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
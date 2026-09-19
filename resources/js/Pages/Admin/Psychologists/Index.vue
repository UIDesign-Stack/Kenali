<script setup>
import { router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    psychologists: { type: Array, required: true },
});

function toggleVerified(psych) {
    router.patch(route('admin.psychologists.toggle-verified', psych.id), {}, { preserveScroll: true });
}

function toggleAvailable(psych) {
    router.patch(route('admin.psychologists.toggle-available', psych.id), {}, { preserveScroll: true });
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Manajemen Psikolog</h1>
                <Link
                    :href="route('admin.users.create-staff')"
                    class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium hover:bg-teal-700"
                >
                    + Tambah Psikolog Baru
                </Link>
            </div>
        </template>

        <div class="max-w-3xl mx-auto p-6">
            <div v-if="$page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ $page.props.flash.success }}
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
                            </div>
                            <p class="text-xs text-gray-400">{{ psych.user.email }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ psych.specialization }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">No. lisensi: {{ psych.license_number }}</p>
                            <p class="text-xs text-gray-400">{{ psych.consultations_count }} konsultasi ditangani</p>
                            <p v-if="psych.active_consultations_count > 0" class="text-xs text-amber-600 mt-0.5">
                                {{ psych.active_consultations_count }} konsultasi masih aktif
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 items-end shrink-0">
                            <Link
                                :href="route('admin.psychologists.consultations', psych.id)"
                                class="text-xs text-gray-500 hover:text-teal-600"
                            >
                                Lihat Konsultasi
                            </Link>
                            <button
                                @click="toggleVerified(psych)"
                                class="text-xs px-3 py-1.5 rounded-md"
                                :class="psych.is_verified
                                    ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    : 'bg-teal-600 text-white hover:bg-teal-700'"
                            >
                                {{ psych.is_verified ? 'Batalkan Verifikasi' : 'Verifikasi' }}
                            </button>
                            <button
                                @click="toggleAvailable(psych)"
                                class="text-xs text-gray-500 hover:text-amber-600"
                            >
                                {{ psych.is_available ? 'Nonaktifkan ketersediaan' : 'Aktifkan ketersediaan' }}
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
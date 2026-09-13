<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    role: { type: String, required: true },
    stats: { type: Object, default: () => ({}) },
    topAlternatives: { type: Array, default: () => [] },
    recentSessions: { type: Array, default: () => [] },
    profile: { type: Object, default: null },
    recentConsultations: { type: Array, default: () => [] },
    latestSession: { type: Object, default: null },
    profileMissing: { type: Boolean, default: false },
});

const statusLabel = {
    in_progress: 'Sedang berjalan',
    completed: 'Selesai',
    pending: 'Menunggu respon',
    scheduled: 'Terjadwal',
    cancelled: 'Dibatalkan',
};

const statusColor = {
    completed: 'bg-green-100 text-green-700',
    in_progress: 'bg-amber-100 text-amber-700',
    pending: 'bg-amber-100 text-amber-700',
    scheduled: 'bg-blue-100 text-blue-700',
    cancelled: 'bg-gray-200 text-gray-500',
};

function statLabel(status) {
    return statusLabel[status] ?? status;
}

function statColor(status) {
    return statusColor[status] ?? 'bg-gray-100 text-gray-500';
}

function stat(value) {
    return value ?? 0;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';

    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';

    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function topAlternativeName(session) {
    return session?.result?.details?.[0]?.alternative?.name ?? null;
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>
        </template>

        <div class="max-w-4xl mx-auto p-6 space-y-8">

            <!-- ================= ADMIN ================= -->
            <template v-if="role === 'admin'">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.total_users) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total User</p>
                        <p class="text-[10px] text-gray-400">{{ stat(stats.active_users) }} aktif</p>
                    </div>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.total_psychologists) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total Psikolog</p>
                        <p class="text-[10px] text-gray-400">{{ stat(stats.verified_psychologists) }} terverifikasi</p>
                    </div>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.total_test_sessions) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total Tes</p>
                        <p class="text-[10px] text-gray-400">{{ stat(stats.completed_test_sessions) }} selesai</p>
                    </div>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.total_consultations) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total Konsultasi</p>
                        <p class="text-[10px] text-gray-400">{{ stat(stats.pending_consultations) }} menunggu</p>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                        Bidang Paling Sering Direkomendasikan
                    </h2>
                    <div v-if="topAlternatives.length === 0" class="text-sm text-gray-400 text-center py-6">
                        Belum ada data hasil tes.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="item in topAlternatives" :key="item.name">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span>{{ item.name }}</span>
                                <span>{{ item.total }}x</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-teal-500" :style="{ width: item.percent + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                        Aktivitas Tes Terbaru
                    </h2>
                    <div class="space-y-2">
                        <div
                            v-for="session in recentSessions"
                            :key="session.id"
                            class="flex items-center justify-between p-3 border border-gray-200 rounded-md text-sm"
                        >
                            <div>
                                <span class="font-medium text-gray-700">{{ session.user?.name ?? 'User tidak diketahui' }}</span>
                                <span class="text-gray-400 ml-2 capitalize">({{ session.life_phase }})</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-[10px] px-2 py-0.5 rounded-full"
                                    :class="statColor(session.status)"
                                >
                                    {{ statLabel(session.status) }}
                                </span>
                                <span class="text-xs text-gray-400">{{ formatDate(session.started_at) }}</span>
                            </div>
                        </div>
                        <p v-if="recentSessions.length === 0" class="text-sm text-gray-400 text-center py-6">
                            Belum ada aktivitas tes.
                        </p>
                    </div>
                </div>
            </template>

            <!-- ================= PSIKOLOG ================= -->
            <template v-else-if="role === 'psikolog'">
                <div v-if="profileMissing" class="p-6 border border-amber-200 bg-amber-50 rounded-lg text-center text-sm text-amber-700">
                    Akun ini belum punya profil psikolog. Hubungi admin.
                </div>

                <template v-else>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.pending_consultations) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Menunggu respon</p>
                        </div>
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.scheduled_consultations) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Terjadwal</p>
                        </div>
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.completed_consultations) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Selesai ditangani</p>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                            Konsultasi Terbaru
                        </h2>
                        <div class="space-y-2">
                            <Link
                                v-for="c in recentConsultations"
                                :key="c.id"
                                :href="route('psikolog.consultations.show', c.id)"
                                class="flex items-center justify-between p-3 border border-gray-200 rounded-md text-sm hover:border-teal-300"
                            >
                                <span class="font-medium text-gray-700">{{ c.user?.name ?? 'User tidak diketahui' }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full" :class="statColor(c.status)">
                                    {{ statLabel(c.status) }}
                                </span>
                            </Link>
                            <p v-if="recentConsultations.length === 0" class="text-sm text-gray-400 text-center py-6">
                                Belum ada konsultasi masuk.
                            </p>
                        </div>
                    </div>
                </template>
            </template>

            <!-- ================= USER ================= -->
            <template v-else>
                <div class="grid grid-cols-3 gap-4">
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.total_tests) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total Tes</p>
                    </div>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.completed_tests) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Tes Selesai</p>
                    </div>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <p class="text-2xl font-semibold text-gray-800">{{ stat(stats.total_consultations) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Konsultasi</p>
                    </div>
                </div>

                <div v-if="latestSession" class="p-4 border border-teal-200 bg-teal-50 rounded-lg">
                    <p class="text-xs text-teal-600 mb-1">Tes terakhirmu</p>
                    <p class="text-sm font-semibold text-gray-800 capitalize mb-1">{{ latestSession.life_phase }}</p>
                    <p v-if="topAlternativeName(latestSession)" class="text-sm text-gray-600">
                        Rekomendasi: {{ topAlternativeName(latestSession) }}
                    </p>
                    <Link :href="route('tests.show', latestSession.id)" class="text-xs text-teal-700 underline mt-2 inline-block">
                        Lihat detail →
                    </Link>
                </div>

                <div v-else class="p-6 border border-gray-200 rounded-lg text-center">
                    <p class="text-sm text-gray-500 mb-3">Kamu belum pernah mengisi tes.</p>
                    <Link :href="route('tests.create')" class="text-sm text-teal-600 font-medium hover:underline">
                        Mulai tes pertamamu →
                    </Link>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    sessions: { type: Array, required: true },
});

const statusLabel = {
    in_progress: 'Sedang berjalan',
    completed: 'Selesai',
};

const statusColor = {
    completed: 'bg-green-100 text-green-700',
    in_progress: 'bg-amber-100 text-amber-700',
};

const lifePhaseLabel = {
    siswa: 'Siswa',
    mahasiswa: 'Mahasiswa',
    pekerja: 'Pekerja',
};

function topAlternativeName(session) {
    return session.result?.details?.[0]?.alternative?.name ?? null;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';

    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';

    return date.toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Riwayat Tes</h1>
                <Link
                    :href="route('tests.create')"
                    class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium hover:bg-teal-700"
                >
                    + Tes Baru
                </Link>
            </div>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <div v-if="sessions.length === 0" class="text-center py-16">
                <p class="text-sm text-gray-400 mb-4">Kamu belum pernah mengisi tes.</p>
                <Link
                    :href="route('tests.create')"
                    class="text-sm text-teal-600 font-medium hover:underline"
                >
                    Mulai tes pertamamu →
                </Link>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="session in sessions"
                    :key="session.id"
                    :href="route('tests.show', session.id)"
                    class="block p-4 border border-gray-200 rounded-lg hover:border-teal-300 transition"
                >
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold text-gray-800">
                            {{ lifePhaseLabel[session.life_phase] ?? session.life_phase }}
                        </span>
                        <span
                            class="text-[10px] px-2 py-0.5 rounded-full"
                            :class="statusColor[session.status] ?? 'bg-gray-100 text-gray-500'"
                        >
                            {{ statusLabel[session.status] ?? session.status }}
                        </span>
                    </div>

                    <p class="text-xs text-gray-400">
                        {{ formatDate(session.started_at) }}
                    </p>

                    <p v-if="topAlternativeName(session)" class="text-sm text-teal-600 mt-2">
                        Rekomendasi utama: {{ topAlternativeName(session) }}
                    </p>
                    <p v-else-if="session.status === 'in_progress'" class="text-xs text-gray-400 mt-2">
                        Klik untuk melanjutkan tes →
                    </p>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
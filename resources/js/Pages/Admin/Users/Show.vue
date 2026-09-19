<script setup>
import { Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
    testSessions: { type: Array, required: true },
});

const page = usePage();

const statusLabels = {
    in_progress: 'Sedang berlangsung',
    completed: 'Selesai',
};

function statusLabel(status) {
    return statusLabels[status] ?? status;
}

function topAlternativeName(session) {
    return session.result?.topDetail?.alternative?.name ?? null;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';

    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';

    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <Head :title="user.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.users.index')" class="text-sm text-gray-400 hover:text-gray-600">
                        ← User
                    </Link>
                    <h1 class="text-xl font-semibold text-gray-800">{{ user.name }}</h1>
                </div>
                <Link
                    :href="route('admin.users.edit', user.id)"
                    class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium hover:bg-teal-700"
                >
                    Edit
                </Link>
            </div>
        </template>

        <div class="max-w-2xl mx-auto p-6">
            <div v-if="page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ page.props.flash.success }}
            </div>

            <div class="p-4 border border-gray-200 rounded-lg mb-6">
                <p class="text-sm text-gray-500">Email</p>
                <p class="text-sm text-gray-800 mb-2">{{ user.email }}</p>
                <p class="text-sm text-gray-500">Fase hidup</p>
                <p class="text-sm text-gray-800 capitalize mb-2">{{ user.life_phase ?? '-' }}</p>
                <p class="text-sm text-gray-500">Role</p>
                <p class="text-sm text-gray-800 capitalize">
                    {{ user.roles?.map(r => r.name).join(', ') || '-' }}
                </p>
            </div>

            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                Riwayat Tes ({{ testSessions.length }})
            </h2>

            <div class="space-y-2">
                <div
                    v-for="session in testSessions"
                    :key="session.id"
                    class="p-3 border border-gray-200 rounded-md"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 capitalize">{{ session.life_phase }}</span>
                        <span class="text-xs text-gray-400">{{ formatDate(session.started_at) }}</span>
                    </div>
                    <p v-if="topAlternativeName(session)" class="text-xs text-teal-600 mt-1">
                        Rekomendasi: {{ topAlternativeName(session) }}
                    </p>
                    <p v-else class="text-xs text-gray-400 mt-1">{{ statusLabel(session.status) }}</p>
                </div>

                <p v-if="testSessions.length === 0" class="text-sm text-gray-400 text-center py-6">
                    User ini belum pernah mengisi tes.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
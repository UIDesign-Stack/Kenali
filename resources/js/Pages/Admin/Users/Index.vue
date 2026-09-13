<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');
let searchTimeout = null;

function applySearch() {
    clearTimeout(searchTimeout);
    router.get(route('admin.users.index'), { search: search.value || undefined }, {
        preserveState: true,
        replace: true,
    });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applySearch, 400);
});

function clearSearch() {
    search.value = '';
    applySearch();
}

watch(() => props.filters.search, (value) => {
    search.value = value ?? '';
});

const togglingId = ref(null);

function toggleActive(user) {
    if (togglingId.value) return;
    if (!confirm(`Yakin ingin ${user.is_active ? 'menonaktifkan' : 'mengaktifkan'} akun ${user.name}?`)) return;

    togglingId.value = user.id;
    router.patch(route('admin.users.toggle-active', user.id), {}, {
        preserveScroll: true,
        onFinish: () => (togglingId.value = null),
    });
}

function roleLabel(user) {
    return user.roles.map((r) => r.name).join(', ') || '-';
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Manajemen User</h1>
        </template>

        <div class="max-w-4xl mx-auto p-6">
            <div v-if="$page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ $page.props.flash.success }}
            </div>

            <div class="relative mb-4">
                <input
                    v-model="search"
                    @keyup.enter="applySearch"
                    type="text"
                    placeholder="Cari nama atau email…"
                    class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 pr-16"
                />
                <button
                    v-if="search"
                    @click="clearSearch"
                    type="button"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400 hover:text-gray-600"
                >
                    Hapus
                </button>
            </div>

            <div class="space-y-2">
                <div
                    v-for="user in users.data"
                    :key="user.id"
                    class="p-4 border border-gray-200 rounded-lg flex items-center justify-between"
                    :class="{ 'opacity-50 bg-gray-50': !user.is_active }"
                >
                    <Link :href="route('admin.users.show', user.id)" class="flex-1">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-sm font-semibold text-gray-800">{{ user.name }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 capitalize">
                                {{ roleLabel(user) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400">{{ user.email }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ user.test_sessions_count }} kali tes</p>
                    </Link>

                    <button
                        @click="toggleActive(user)"
                        :disabled="togglingId === user.id"
                        class="text-xs shrink-0 ml-4 disabled:opacity-40"
                        :class="user.is_active ? 'text-red-500 hover:text-red-700' : 'text-teal-600 hover:text-teal-800'"
                    >
                        {{ togglingId === user.id ? '...' : (user.is_active ? 'Nonaktifkan' : 'Aktifkan') }}
                    </button>
                </div>

                <p v-if="users.data.length === 0" class="text-sm text-gray-400 text-center py-8">
                    Tidak ada user ditemukan.
                </p>
            </div>

            <div v-if="users.links.length > 3" class="flex flex-wrap gap-1 mt-6 justify-center">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    v-html="link.label"
                    class="px-3 py-1.5 text-xs rounded-md border"
                    :class="[
                        link.active ? 'bg-teal-600 text-white border-teal-600' : 'border-gray-200 text-gray-600',
                        !link.url ? 'opacity-40 pointer-events-none' : 'hover:border-teal-300',
                    ]"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
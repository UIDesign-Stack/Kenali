<script setup>
import { ref } from 'vue';
import { router, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const page = usePage();

const search = ref(props.filters.search ?? '');

// Lacak user mana yang sedang diproses, supaya tombolnya di-disable dan
// mencegah double-click mengirim request dobel.
const processingId = ref(null);

function applySearch() {
    router.get(route('admin.users.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    });
}

function toggleActive(user) {
    if (!confirm(`Yakin ingin ${user.is_active ? 'menonaktifkan' : 'mengaktifkan'} akun ${user.name}?`)) return;

    processingId.value = user.id;

    router.patch(route('admin.users.toggle-active', user.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
        },
    });
}

function roleLabel(user) {
    return user.roles.map((r) => r.name).join(', ') || '-';
}

// ---------------------------------------------------------------
// UI only: warna avatar & lencana peran
// ---------------------------------------------------------------
const avatarColors = [
    'bg-indigo-100 text-indigo-700',
    'bg-teal-100 text-teal-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-sky-100 text-sky-700',
    'bg-violet-100 text-violet-700',
];

function avatarColor(user) {
    return avatarColors[Math.abs(Number(user.id) || 0) % avatarColors.length];
}

function initials(name) {
    if (!name) return '?';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() ?? '')
        .join('');
}

// Label paginasi Laravel berisi entity HTML (mis. "&laquo; Previous").
// Didekode manual supaya tidak perlu v-html (aman dari XSS & bebas warning ESLint).
function pageLabel(label) {
    return String(label ?? '')
        .replace(/<[^>]*>/g, '')
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&lsaquo;/g, '‹')
        .replace(/&rsaquo;/g, '›')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .trim();
}

function roleChipClass(roleName) {
    const key = String(roleName ?? '').toLowerCase();
    if (key === 'admin') return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    if (key === 'psikolog') return 'bg-teal-50 text-teal-700 ring-teal-600/20';
    if (key === 'user') return 'bg-indigo-50 text-indigo-700 ring-indigo-600/20';
    return 'bg-gray-100 text-gray-600 ring-gray-500/20';
}
</script>

<template>
    <Head title="Manajemen User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-gray-800">Manajemen User</h1>
                <Link
                    :href="route('admin.users.create-staff')"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Buat Akun Staff
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

            <!-- Notifikasi -->
            <div
                v-if="page.props.flash?.success"
                class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200"
                role="status"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </span>
                {{ page.props.flash.success }}
            </div>

            <div
                v-if="page.props.errors?.user"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                role="alert"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ page.props.errors.user }}</span>
            </div>

            <!-- Pencarian -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input
                        v-model="search"
                        @keyup.enter="applySearch"
                        type="text"
                        aria-label="Cari nama atau email"
                        placeholder="Cari nama atau email…"
                        class="w-full rounded-2xl border-gray-200 bg-white py-3 pl-12 pr-4 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-400 focus:ring-indigo-400"
                    />
                </div>
                <button
                    type="button"
                    @click="applySearch"
                    class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    Cari
                </button>
            </div>

            <!-- Daftar user -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="user in users.data"
                    :key="user.id"
                    class="flex flex-col rounded-3xl p-5 ring-1 transition"
                    :class="user.is_active ? 'bg-white ring-gray-200/80 shadow-sm hover:shadow-md' : 'bg-gray-50 ring-gray-200/60'"
                >
                    <Link
                        :href="route('admin.users.show', user.id)"
                        class="flex flex-1 items-start gap-3 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        :class="{ 'opacity-60': !user.is_active }"
                    >
                        <span
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                            :class="avatarColor(user)"
                        >
                            {{ initials(user.name) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ user.name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ user.email }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5" :title="roleLabel(user)">
                                <span
                                    v-for="role in user.roles"
                                    :key="role.name"
                                    class="rounded-full px-2.5 py-0.5 text-[11px] font-medium capitalize ring-1 ring-inset"
                                    :class="roleChipClass(role.name)"
                                >
                                    {{ role.name }}
                                </span>
                                <span v-if="!user.roles.length" class="text-xs text-gray-400">-</span>
                            </div>
                        </div>
                    </Link>

                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-indigo-50 px-1.5 font-bold text-indigo-700">
                                {{ user.test_sessions_count }}
                            </span>
                            kali tes
                        </div>

                        <button
                            type="button"
                            @click="toggleActive(user)"
                            :disabled="processingId === user.id"
                            class="rounded-xl px-3.5 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="user.is_active
                                ? 'bg-rose-50 text-rose-600 hover:bg-rose-100 focus-visible:ring-rose-400'
                                : 'bg-teal-50 text-teal-700 hover:bg-teal-100 focus-visible:ring-teal-400'"
                        >
                            {{ processingId === user.id ? '...' : (user.is_active ? 'Nonaktifkan' : 'Aktifkan') }}
                        </button>
                    </div>
                </article>
            </div>

            <div
                v-if="users.data.length === 0"
                class="flex flex-col items-center rounded-3xl border border-dashed border-gray-300 bg-white py-14 text-center"
            >
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-indigo-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                </span>
                <p class="mt-3 text-sm text-gray-500">Tidak ada user ditemukan.</p>
            </div>

            <!-- Paginasi -->
            <nav v-if="users.links.length > 3" class="flex flex-wrap justify-center gap-1.5 pt-2" aria-label="Paginasi">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="min-w-[2.25rem] rounded-xl px-3 py-2 text-center text-xs font-medium ring-1 ring-inset transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                    :class="[
                        link.active
                            ? 'bg-indigo-600 text-white ring-indigo-600 shadow-md shadow-indigo-500/30'
                            : 'bg-white text-gray-600 ring-gray-200',
                        !link.url ? 'pointer-events-none opacity-40' : (link.active ? '' : 'hover:bg-indigo-50 hover:text-indigo-700 hover:ring-indigo-200'),
                    ]"
                >{{ pageLabel(link.label) }}</Link>
            </nav>
        </div>
    </AuthenticatedLayout>
</template>
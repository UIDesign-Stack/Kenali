<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const showingNavigationDropdown = ref(false);

const userRoles = computed(() => page.props.auth.user?.roles ?? []);
const isUser = computed(() => userRoles.value.includes('user'));
const isPsikolog = computed(() => userRoles.value.includes('psikolog'));
const isAdmin = computed(() => userRoles.value.includes('admin'));

const avatarUrl = computed(() => page.props.auth.user?.avatar_url ?? null);
const userInitial = computed(() =>
    (page.props.auth.user?.name ?? '?').trim().charAt(0).toUpperCase()
);


const primaryRole = computed(() => {
    const r = userRoles.value[0];
    return r ? r.charAt(0).toUpperCase() + r.slice(1) : '';
});

function closeMobileNav() {
    showingNavigationDropdown.value = false;
}

// ---------------------------------------------------------------
// Daftar menu. Route, aturan peran, dan logika "active" SAMA dengan
// versi sebelumnya -- hanya dikumpulkan jadi satu daftar supaya menu
// desktop & mobile tidak duplikat. `page.url` dibaca agar status
// active ikut diperbarui setiap pindah halaman.
// ---------------------------------------------------------------
const icons = {
    dashboard: 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
    tests: 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z',
    chat: 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155',
    ahp: 'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75',
    questions: 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z',
    alternatives: 'M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122',
    users: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
    psychologists: 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
};

const navItems = computed(() => {
    void page.url;

    const items = [
        {
            key: 'dashboard', label: 'Dashboard', icon: icons.dashboard,
            href: route('dashboard'), active: route().current('dashboard'),
            on: 'bg-indigo-50 text-indigo-700', dot: 'text-indigo-500',
        },
    ];

    if (isUser.value) {
        items.push(
            {
                key: 'tests', label: 'Tes Saya', icon: icons.tests,
                href: route('tests.index'), active: route().current('tests.*'),
                on: 'bg-violet-50 text-violet-700', dot: 'text-violet-500',
            },
            {
                key: 'consultations', label: 'Konsultasi', icon: icons.chat,
                href: route('consultations.index'), active: route().current('consultations.*'),
                on: 'bg-rose-50 text-rose-700', dot: 'text-rose-500',
            },
        );
    }

    if (isPsikolog.value) {
        items.push({
            key: 'psikolog-consultations', label: 'Konsultasi Masuk', icon: icons.chat,
            href: route('psikolog.consultations.index'), active: route().current('psikolog.*'),
            on: 'bg-teal-50 text-teal-700', dot: 'text-teal-500',
        });
    }

    if (isAdmin.value) {
        items.push(
            {
                key: 'ahp', label: 'Bobot AHP', icon: icons.ahp,
                href: route('admin.ahp.criteria.index'), active: route().current('admin.ahp.*'),
                on: 'bg-violet-50 text-violet-700', dot: 'text-violet-500',
            },
            {
                key: 'questions', label: 'Bank Soal', icon: icons.questions,
                href: route('admin.questions.index'), active: route().current('admin.questions.*'),
                on: 'bg-teal-50 text-teal-700', dot: 'text-teal-500',
            },
            {
                key: 'alternatives', label: 'Alternatif', icon: icons.alternatives,
                href: route('admin.alternatives.index'),
                active: route().current('admin.alternatives.*') || route().current('admin.alternative-profiles.*'),
                on: 'bg-amber-50 text-amber-700', dot: 'text-amber-500',
            },
            {
                key: 'users', label: 'User', icon: icons.users,
                href: route('admin.users.index'), active: route().current('admin.users.*'),
                on: 'bg-sky-50 text-sky-700', dot: 'text-sky-500',
            },
            {
                key: 'psychologists', label: 'Psikolog', icon: icons.psychologists,
                href: route('admin.psychologists.index'), active: route().current('admin.psychologists.*'),
                on: 'bg-rose-50 text-rose-700', dot: 'text-rose-500',
            },
        );
    }

    return items;
});
</script>

<template>
    <div>
        <div class="min-h-screen bg-gradient-to-b from-indigo-50/70 via-slate-50 to-slate-50">
            <nav class="sticky top-0 z-40 border-b border-gray-200/70 bg-white/85 backdrop-blur-md">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between">
                        <div class="flex items-center">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="route('dashboard')"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 via-violet-600 to-rose-500 shadow-md shadow-indigo-500/30 transition hover:scale-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 motion-reduce:transition-none motion-reduce:hover:scale-100"
                                    aria-label="Ke Dashboard"
                                >
                                    <ApplicationLogo class="block h-6 w-auto fill-current text-white" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden sm:ms-8 sm:flex sm:items-center sm:gap-1">
                                <Link
                                    v-for="item in navItems"
                                    :key="item.key"
                                    :href="item.href"
                                    :aria-current="item.active ? 'page' : undefined"
                                    class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                    :class="item.active
                                        ? [item.on, 'font-semibold']
                                        : 'font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800'"
                                >
                                    <svg
                                        class="h-[18px] w-[18px] shrink-0"
                                        :class="item.active ? '' : 'text-gray-400'"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                    </svg>
                                    {{ item.label }}
                                </Link>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-2">
                            <NotificationBell />
                            <!-- Settings Dropdown -->
                            <div class="relative ms-1">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-full">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2.5 rounded-full border border-gray-200 bg-white py-1.5 pe-3 ps-1.5 text-sm font-medium text-gray-600 transition hover:border-indigo-200 hover:bg-indigo-50/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                            >
                                                <img
                                                    v-if="avatarUrl"
                                                    :src="avatarUrl"
                                                    alt=""
                                                    class="h-8 w-8 rounded-full object-cover ring-2 ring-white"
                                                />
                                                <span
                                                    v-else
                                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-teal-400 to-indigo-500 text-xs font-bold text-white"
                                                    aria-hidden="true"
                                                >
                                                    {{ userInitial }}
                                                </span>

                                                <span class="flex flex-col items-start text-left leading-tight">
                                                    <span class="max-w-[9rem] truncate text-sm font-semibold text-gray-800">
                                                        {{ page.props.auth.user?.name ?? '' }}
                                                    </span>
                                                    <span v-if="primaryRole" class="text-[11px] font-medium text-indigo-500">
                                                        {{ primaryRole }}
                                                    </span>
                                                </span>

                                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                :aria-expanded="showingNavigationDropdown"
                                aria-label="Buka menu navigasi"
                                class="inline-flex items-center justify-center rounded-xl p-2 text-gray-500 transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="space-y-1 px-3 pb-3 pt-2">
                        <Link
                            v-for="item in navItems"
                            :key="item.key"
                            :href="item.href"
                            :aria-current="item.active ? 'page' : undefined"
                            @click="closeMobileNav"
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-base transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            :class="item.active
                                ? [item.on, 'font-semibold']
                                : 'font-medium text-gray-600 hover:bg-gray-100'"
                        >
                            <svg
                                class="h-5 w-5 shrink-0"
                                :class="item.active ? '' : 'text-gray-400'"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            {{ item.label }}
                        </Link>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-gray-200 pb-3 pt-4">
                        <div class="flex items-center gap-3 px-4">
                            <img
                                v-if="avatarUrl"
                                :src="avatarUrl"
                                alt=""
                                class="h-10 w-10 shrink-0 rounded-full object-cover"
                            />
                            <span
                                v-else
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-400 to-indigo-500 text-sm font-bold text-white"
                                aria-hidden="true"
                            >
                                {{ userInitial }}
                            </span>
                            <div class="min-w-0">
                                <div class="truncate text-base font-semibold text-gray-800">
                                    {{ page.props.auth.user?.name ?? '' }}
                                </div>
                                <div class="truncate text-sm font-medium text-gray-500">
                                    {{ page.props.auth.user?.email ?? '' }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1 px-3">
                            <Link
                                :href="route('profile.edit')"
                                @click="closeMobileNav"
                                class="block rounded-xl px-3 py-2.5 text-base font-medium text-gray-600 transition hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            >
                                Profile
                            </Link>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="block w-full rounded-xl px-3 py-2.5 text-left text-base font-medium text-rose-600 transition hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                            >
                                Log Out
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                <div class="[&_h1]:text-2xl [&_h1]:font-bold [&_h1]:tracking-tight [&_h1]:text-gray-900">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>

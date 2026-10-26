<script setup>
import { ref } from 'vue';
import { useForm, router, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
    roles: { type: Array, required: true },
    // Dikirim dari UserManagementController::edit().
    lifePhaseOptions: { type: Object, default: () => ({}) },
});

const page = usePage();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone,
    life_phase: props.user.life_phase,
    role: props.user.roles[0]?.name ?? 'user',
});

const sendingReset = ref(false);

function submit() {
    form.put(route('admin.users.update', props.user.id));
}

function sendResetPasswordLink() {
    if (!confirm(`Kirim link reset password ke email ${props.user.email}?`)) return;

    sendingReset.value = true;

    router.post(route('admin.users.reset-password', props.user.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            sendingReset.value = false;
        },
    });
}

// ---------------------------------------------------------------
// UI only: gaya input, avatar, dan warna pilihan role
// ---------------------------------------------------------------
const baseInput =
    'w-full rounded-xl border-gray-200 bg-gray-50 text-sm placeholder:text-gray-400 transition focus:border-indigo-400 focus:bg-white focus:ring-indigo-400';
const errorInput = 'border-rose-400 bg-rose-50/40 focus:border-rose-400 focus:ring-rose-400';

function inputClass(field) {
    return [baseInput, form.errors[field] ? errorInput : ''];
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

function roleOn(roleName) {
    const key = String(roleName).toLowerCase();
    if (key === 'admin') return 'border-rose-400 bg-rose-50 text-rose-700 ring-2 ring-rose-400/20';
    if (key === 'psikolog') return 'border-teal-500 bg-teal-50 text-teal-700 ring-2 ring-teal-500/20';
    return 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-500/20';
}
</script>

<template>
    <Head :title="`Edit — ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('admin.users.show', user.id)"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← {{ user.name }}
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Edit User</h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">
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
                v-if="page.props.errors?.email"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                role="alert"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ page.props.errors.email }}</span>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

                <!-- ===== Kiri: profil + reset password ===== -->
                <aside class="space-y-4 lg:col-span-2">
                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900 via-indigo-700 to-violet-600 p-6 text-white shadow-xl shadow-indigo-900/20 sm:p-8">
                        <div class="pointer-events-none absolute -top-14 -right-10 h-48 w-48 rounded-full bg-rose-400/30 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-16 left-6 h-48 w-48 rounded-full bg-teal-300/25 blur-3xl"></div>

                        <div class="relative flex items-center gap-4">
                            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-2xl font-bold ring-1 ring-white/30 backdrop-blur">
                                {{ initials(user.name) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-lg font-bold">{{ user.name }}</p>
                                <p class="truncate text-sm text-indigo-100">{{ user.email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            </span>
                            <h2 class="text-sm font-semibold text-gray-800">Reset password</h2>
                        </div>
                        <button
                            type="button"
                            @click="sendResetPasswordLink"
                            :disabled="sendingReset"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 ring-1 ring-amber-200 transition hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ sendingReset ? 'Mengirim…' : 'Kirim Link Reset Password ke Email User' }}
                        </button>
                    </div>
                </aside>

                <!-- ===== Kanan: form ===== -->
                <form
                    @submit.prevent="submit"
                    class="rounded-3xl bg-white p-6 ring-1 ring-gray-200/80 shadow-sm sm:p-8 lg:col-span-3"
                >
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="edit-name" class="mb-1.5 block text-sm font-medium text-gray-700">Nama</label>
                                <input id="edit-name" v-model="form.name" type="text" :class="inputClass('name')" :aria-invalid="!!form.errors.name" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label for="edit-email" class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                                <input id="edit-email" v-model="form.email" type="email" :class="inputClass('email')" :aria-invalid="!!form.errors.email" />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="edit-phone" class="mb-1.5 block text-sm font-medium text-gray-700">Telepon</label>
                                <input id="edit-phone" v-model="form.phone" type="text" :class="inputClass('phone')" />
                            </div>

                            <div>
                                <label for="edit-life-phase" class="mb-1.5 block text-sm font-medium text-gray-700">Fase Hidup</label>
                                <select id="edit-life-phase" v-model="form.life_phase" :class="inputClass('life_phase')">
                                    <option :value="null">-</option>
                                    <option v-for="(label, value) in lifePhaseOptions" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.life_phase" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.life_phase }}</p>
                            </div>
                        </div>

                        <fieldset>
                            <legend class="mb-2 block text-sm font-medium text-gray-700">Role</legend>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="role in roles"
                                    :key="role"
                                    class="cursor-pointer rounded-xl border px-4 py-2 text-sm font-semibold capitalize transition focus-within:ring-2 focus-within:ring-indigo-400"
                                    :class="form.role === role ? roleOn(role) : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'"
                                >
                                    <input type="radio" v-model="form.role" :value="role" class="sr-only" />
                                    {{ role }}
                                </label>
                            </div>
                            <p v-if="form.errors.role" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.role }}</p>
                        </fieldset>
                    </div>

                    <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <Link
                            :href="route('admin.users.show', user.id)"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                            </svg>
                            {{ form.processing ? 'Menyimpan…' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<script setup>
import { ref } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'psikolog',
    license_number: '',
    specialization: '',
});

function submit() {
    form.post(route('admin.users.store-staff'));
}

// ---------------------------------------------------------------
// UI only: gaya input, pilihan role, dan tampil/sembunyi password
// ---------------------------------------------------------------
const showPassword = ref(false);

const baseInput =
    'w-full rounded-xl border-gray-200 bg-gray-50 text-sm placeholder:text-gray-400 transition focus:border-indigo-400 focus:bg-white focus:ring-indigo-400';
const errorInput = 'border-rose-400 bg-rose-50/40 focus:border-rose-400 focus:ring-rose-400';

function inputClass(field) {
    return [baseInput, form.errors[field] ? errorInput : ''];
}

const roles = [
    {
        value: 'psikolog',
        label: 'Psikolog',
        hint: 'Menangani konsultasi',
        on: 'border-teal-500 bg-teal-50 ring-2 ring-teal-500/20',
        dot: 'bg-teal-500',
    },
    {
        value: 'admin',
        label: 'Admin',
        hint: 'Mengelola platform',
        on: 'border-rose-400 bg-rose-50 ring-2 ring-rose-400/20',
        dot: 'bg-rose-400',
    },
];
</script>

<template>
    <Head title="Buat Akun Staff" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('admin.users.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← User
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Buat Akun Staff</h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

                <!-- ===== Penjelasan ===== -->
                <aside class="lg:col-span-2">
                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900 via-indigo-700 to-violet-600 p-6 text-white shadow-xl shadow-indigo-900/20 sm:p-8 lg:sticky lg:top-24">
                        <div class="pointer-events-none absolute -top-14 -right-10 h-48 w-48 rounded-full bg-rose-400/30 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-16 left-6 h-48 w-48 rounded-full bg-teal-300/25 blur-3xl"></div>

                        <div class="relative">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 backdrop-blur">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                            </span>
                            <h2 class="mt-4 text-xl font-bold tracking-tight">Akun khusus staff</h2>
                            <p class="mt-2 text-sm leading-relaxed text-indigo-100">
                                Akun admin dan psikolog tidak bisa didaftar lewat halaman register publik --
                                harus dibuat khusus di sini oleh admin.
                            </p>
                        </div>
                    </div>
                </aside>

                <!-- ===== Form ===== -->
                <form
                    @submit.prevent="submit"
                    class="lg:col-span-3 rounded-3xl bg-white p-6 ring-1 ring-gray-200/80 shadow-sm sm:p-8"
                >
                    <div class="space-y-6">
                        <!-- Role -->
                        <fieldset>
                            <legend class="mb-2 block text-sm font-medium text-gray-700">Role</legend>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    v-for="r in roles"
                                    :key="r.value"
                                    class="relative flex cursor-pointer items-center gap-3 rounded-2xl border p-4 transition focus-within:ring-2 focus-within:ring-indigo-400"
                                    :class="form.role === r.value ? r.on : 'border-gray-200 bg-white hover:bg-gray-50'"
                                >
                                    <input type="radio" v-model="form.role" :value="r.value" class="sr-only" />
                                    <span
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                                        :class="form.role === r.value ? 'border-transparent ' + r.dot : 'border-gray-300'"
                                    >
                                        <span v-if="form.role === r.value" class="h-2 w-2 rounded-full bg-white"></span>
                                    </span>
                                    <span>
                                        <span class="block text-sm font-semibold text-gray-800">{{ r.label }}</span>
                                        <span class="block text-xs text-gray-500">{{ r.hint }}</span>
                                    </span>
                                </label>
                            </div>
                        </fieldset>

                        <!-- Nama & Email -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="staff-name" class="mb-1.5 block text-sm font-medium text-gray-700">Nama</label>
                                <input id="staff-name" v-model="form.name" type="text" :class="inputClass('name')" :aria-invalid="!!form.errors.name" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label for="staff-email" class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                                <input id="staff-email" v-model="form.email" type="email" :class="inputClass('email')" :aria-invalid="!!form.errors.email" />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="staff-password" class="mb-1.5 block text-sm font-medium text-gray-700">Password Awal</label>
                                <input id="staff-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" :class="inputClass('password')" :aria-invalid="!!form.errors.password" />
                                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.password }}</p>
                            </div>

                            <div>
                                <label for="staff-password-confirm" class="mb-1.5 block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                                <input id="staff-password-confirm" v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" :class="inputClass('password_confirmation')" />
                            </div>
                        </div>

                        <label class="-mt-2 inline-flex cursor-pointer items-center gap-2 text-xs text-gray-500">
                            <input v-model="showPassword" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-400" />
                            Tampilkan password
                        </label>

                        <!-- Khusus psikolog -->
                        <div v-if="form.role === 'psikolog'" class="rounded-2xl bg-teal-50/60 p-5 ring-1 ring-teal-100">
                            <p class="mb-4 text-xs font-semibold text-teal-700">Data psikolog</p>
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="staff-license" class="mb-1.5 block text-sm font-medium text-gray-700">Nomor Lisensi (STR/SIPP)</label>
                                    <input id="staff-license" v-model="form.license_number" type="text" :class="inputClass('license_number')" :aria-invalid="!!form.errors.license_number" />
                                    <p v-if="form.errors.license_number" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.license_number }}</p>
                                </div>

                                <div>
                                    <label for="staff-specialization" class="mb-1.5 block text-sm font-medium text-gray-700">Spesialisasi</label>
                                    <input id="staff-specialization" v-model="form.specialization" type="text" placeholder="Contoh: Psikolog Pendidikan" :class="inputClass('specialization')" :aria-invalid="!!form.errors.specialization" />
                                    <p v-if="form.errors.specialization" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.specialization }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-gray-100 pt-6">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                            </svg>
                            {{ form.processing ? 'Membuat…' : 'Buat Akun' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
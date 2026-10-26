<script setup>
import { computed, ref } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const emailError = computed(() => form.errors.email || page.props.errors?.email);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk - Kenali" />

        <div
            v-if="status"
            class="mb-6 flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200"
            role="status"
        >
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
            </span>
            {{ status }}
        </div>

        <h1 class="display text-4xl font-extrabold leading-tight tracking-tight">
            Selamat <span class="grad-text">datang kembali</span>
        </h1>
        <p class="muted mt-2">Masuk untuk melanjutkan tes dan melihat hasilmu.</p>

        <form class="mt-8" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" class="!font-bold !text-[#1e1b4b]" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full !rounded-xl !border-gray-200 !bg-white/90 !py-3 focus:!border-violet-500 focus:!bg-white focus:!ring-violet-300"
                    :class="{ '!border-rose-400 !bg-rose-50/40': emailError }"
                    v-model="form.email"
                    placeholder="kamu@email.com"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="emailError" />
            </div>

            <div class="mt-5">
                <InputLabel for="password" value="Kata sandi" class="!font-bold !text-[#1e1b4b]" />

                <div class="relative">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full !rounded-xl !border-gray-200 !bg-white/90 !py-3 !pr-12 focus:!border-violet-500 focus:!bg-white focus:!ring-violet-300"
                        :class="{ '!border-rose-400 !bg-rose-50/40': form.errors.password }"
                        v-model="form.password"
                        placeholder="Masukkan kata sandi"
                        required
                        autocomplete="current-password"
                    />

                    <button
                        type="button"
                        class="absolute inset-y-0 right-2 top-1 flex w-10 items-center justify-center rounded-lg text-indigo-950/50 transition hover:text-indigo-950"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :aria-pressed="showPassword"
                        @click="showPassword = !showPassword"
                    >
                        <svg v-if="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3.01 9.96 7.18.07.21.07.43 0 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3.01-9.96-7.18Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 0 0 1.93 12C3.23 16.34 7.24 19.5 12 19.5c.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0 1 12 4.5c4.76 0 8.77 3.16 10.07 7.5a10.52 10.52 0 0 1-4.29 5.77M6.23 6.23 3 3m3.23 3.23 3.65 3.65m7.89 7.89L21 21m-3.23-3.23-3.65-3.65m0 0a3 3 0 1 0-4.24-4.24m4.24 4.24L9.88 9.88" /></svg>
                    </button>
                </div>

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-5 flex items-center justify-between gap-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" class="!text-violet-600 focus:!ring-violet-400" />
                    <span class="ms-2 text-sm font-medium text-gray-700">Ingat saya</span>
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="nav-link text-sm font-semibold text-indigo-700"
                >
                    Lupa kata sandi?
                </Link>
            </div>

            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center !rounded-full !border-0 !bg-gradient-to-r !from-indigo-600 !via-violet-600 !to-pink-600 !py-3.5 !text-sm !font-bold !normal-case !tracking-normal shadow-lg shadow-violet-500/40 hover:!brightness-110 focus:!ring-violet-400"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Memproses...' : 'Masuk' }}
                </PrimaryButton>
            </div>
        </form>

        <p v-if="route().has('register')" class="muted mt-8 text-center text-sm">
            Belum punya akun?
            <Link :href="route('register')" class="font-bold text-indigo-700 hover:underline">Daftar sekarang</Link>
        </p>
    </GuestLayout>
</template>
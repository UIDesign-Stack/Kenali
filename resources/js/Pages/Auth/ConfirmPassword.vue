<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <h1 class="mb-3 text-2xl font-bold tracking-tight text-gray-900">Confirm Password</h1>

        <div class="mb-6 flex items-start gap-3 rounded-2xl bg-indigo-50 p-4 text-sm leading-relaxed text-indigo-900 ring-1 ring-indigo-100">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
            <p>
                This is a secure area of the application. Please confirm your
                password before continuing.
            </p>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full !rounded-xl !border-gray-200 !bg-gray-50 !py-3 focus:!border-indigo-400 focus:!bg-white focus:!ring-indigo-400"
                    :class="{ '!border-rose-400 !bg-rose-50/40': form.errors.password }"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center !rounded-xl !border-0 bg-gradient-to-r from-indigo-600 to-violet-600 !py-3 !text-sm !normal-case !tracking-normal shadow-md shadow-indigo-500/30 hover:from-indigo-700 hover:to-violet-700 focus:!ring-indigo-400"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Confirm
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>

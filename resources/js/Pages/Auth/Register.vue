<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">Register</h1>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full !rounded-xl !border-gray-200 !bg-gray-50 !py-3 focus:!border-indigo-400 focus:!bg-white focus:!ring-indigo-400"
                    :class="{ '!border-rose-400 !bg-rose-50/40': form.errors.name }"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-5">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full !rounded-xl !border-gray-200 !bg-gray-50 !py-3 focus:!border-indigo-400 focus:!bg-white focus:!ring-indigo-400"
                    :class="{ '!border-rose-400 !bg-rose-50/40': form.errors.email }"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <InputLabel for="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full !rounded-xl !border-gray-200 !bg-gray-50 !py-3 focus:!border-indigo-400 focus:!bg-white focus:!ring-indigo-400"
                        :class="{ '!border-rose-400 !bg-rose-50/40': form.errors.password }"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Confirm Password"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full !rounded-xl !border-gray-200 !bg-gray-50 !py-3 focus:!border-indigo-400 focus:!bg-white focus:!ring-indigo-400"
                        :class="{ '!border-rose-400 !bg-rose-50/40': form.errors.password_confirmation }"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />

                    <InputError
                        class="mt-2"
                        :message="form.errors.password_confirmation"
                    />
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-4">
                <PrimaryButton
                    class="w-full justify-center !rounded-xl !border-0 bg-gradient-to-r from-indigo-600 to-violet-600 !py-3 !text-sm !normal-case !tracking-normal shadow-md shadow-indigo-500/30 hover:from-indigo-700 hover:to-violet-700 focus:!ring-indigo-400"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>

                <Link
                    :href="route('login')"
                    class="rounded-md text-center text-sm font-medium text-indigo-600 transition hover:text-indigo-800 hover:underline focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>

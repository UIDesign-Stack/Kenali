<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-900">Reset Password</h1>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full !rounded-xl !border-gray-200 !bg-gray-50 !py-3 focus:!border-indigo-400 focus:!bg-white focus:!ring-indigo-400"
                    :class="{ '!border-rose-400 !bg-rose-50/40': form.errors.email }"
                    v-model="form.email"
                    required
                    autofocus
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

            <div class="mt-6">
                <PrimaryButton
                    class="w-full justify-center !rounded-xl !border-0 bg-gradient-to-r from-indigo-600 to-violet-600 !py-3 !text-sm !normal-case !tracking-normal shadow-md shadow-indigo-500/30 hover:from-indigo-700 hover:to-violet-700 focus:!ring-indigo-400"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Reset Password
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>

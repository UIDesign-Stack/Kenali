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
</script>

<template>
    <Head :title="`Edit — ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.users.show', user.id)" class="text-sm text-gray-400 hover:text-gray-600">
                    ← {{ user.name }}
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Edit User</h1>
            </div>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <div v-if="page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ page.props.flash.success }}
            </div>

            <!-- Error dari aksi reset password (router.post biasa, bukan
                 useForm, jadi tidak otomatis masuk ke form.errors -- harus
                 dibaca manual dari page.props.errors). -->
            <div v-if="page.props.errors?.email" class="mb-4 p-3 rounded-md bg-red-50 text-red-700 text-sm">
                {{ page.props.errors.email }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input v-model="form.name" type="text" class="w-full rounded-md border-gray-300 text-sm" />
                    <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input v-model="form.email" type="email" class="w-full rounded-md border-gray-300 text-sm" />
                    <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                    <input v-model="form.phone" type="text" class="w-full rounded-md border-gray-300 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fase Hidup</label>
                    <select v-model="form.life_phase" class="w-full rounded-md border-gray-300 text-sm">
                        <option :value="null">-</option>
                        <option v-for="(label, value) in lifePhaseOptions" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                    <p v-if="form.errors.life_phase" class="text-xs text-red-600 mt-1">{{ form.errors.life_phase }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select v-model="form.role" class="w-full rounded-md border-gray-300 text-sm capitalize">
                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                    </select>
                    <p v-if="form.errors.role" class="text-xs text-red-600 mt-1">{{ form.errors.role }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full px-5 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    {{ form.processing ? 'Menyimpan…' : 'Simpan Perubahan' }}
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-200">
                <button
                    @click="sendResetPasswordLink"
                    :disabled="sendingReset"
                    class="text-sm text-amber-600 hover:text-amber-800 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ sendingReset ? 'Mengirim…' : 'Kirim Link Reset Password ke Email User' }}
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
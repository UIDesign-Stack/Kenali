<script setup>
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
</script>

<template>
    <Head title="Buat Akun Staff" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.users.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← User
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Buat Akun Staff</h1>
            </div>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <p class="text-sm text-gray-500 mb-6">
                Akun admin dan psikolog tidak bisa didaftar lewat halaman register publik --
                harus dibuat khusus di sini oleh admin.
            </p>

            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select v-model="form.role" class="w-full rounded-md border-gray-300 text-sm capitalize">
                        <option value="psikolog">Psikolog</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Awal</label>
                    <input v-model="form.password" type="password" class="w-full rounded-md border-gray-300 text-sm" />
                    <p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                    <input v-model="form.password_confirmation" type="password" class="w-full rounded-md border-gray-300 text-sm" />
                </div>

                <template v-if="form.role === 'psikolog'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Lisensi (STR/SIPP)</label>
                        <input v-model="form.license_number" type="text" class="w-full rounded-md border-gray-300 text-sm" />
                        <p v-if="form.errors.license_number" class="text-xs text-red-600 mt-1">{{ form.errors.license_number }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Spesialisasi</label>
                        <input v-model="form.specialization" type="text" placeholder="Contoh: Psikolog Pendidikan" class="w-full rounded-md border-gray-300 text-sm" />
                        <p v-if="form.errors.specialization" class="text-xs text-red-600 mt-1">{{ form.errors.specialization }}</p>
                    </div>
                </template>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full px-5 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    {{ form.processing ? 'Membuat…' : 'Buat Akun' }}
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
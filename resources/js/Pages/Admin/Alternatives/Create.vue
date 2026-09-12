<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    name: '',
    description: '',
    icon: '',
    life_phase: 'umum',
});

function submit() {
    form.post(route('admin.alternatives.store'));
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.alternatives.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Alternatif
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Tambah Alternatif</h1>
            </div>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bidang/Karier</label>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Contoh: Sains & Teknik"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    />
                    <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder="Penjelasan singkat tentang bidang ini untuk ditampilkan ke user"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    ></textarea>
                    <p v-if="form.errors.description" class="text-xs text-red-600 mt-1">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ikon (opsional)</label>
                    <input
                        v-model="form.icon"
                        type="text"
                        placeholder="Nama ikon, contoh: flask, palette, users"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku untuk fase</label>
                    <select
                        v-model="form.life_phase"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    >
                        <option value="umum">Umum (semua fase)</option>
                        <option value="siswa">Siswa</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="pekerja">Pekerja</option>
                    </select>
                    <p v-if="form.errors.life_phase" class="text-xs text-red-600 mt-1">{{ form.errors.life_phase }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-5 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    Simpan
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
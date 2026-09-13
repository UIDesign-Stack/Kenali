<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    alternative: { type: Object, required: true },
    lifePhaseOptions: { type: Object, default: () => ({}) },
});

const form = useForm({
    name: props.alternative.name,
    description: props.alternative.description,
    icon: props.alternative.icon,
    life_phase: props.alternative.life_phase,
});

function submit() {
    form.put(route('admin.alternatives.update', props.alternative.id));
}
</script>

<template>
    <Head :title="`Edit — ${alternative.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.alternatives.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Alternatif
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">Edit — {{ alternative.name }}</h1>
            </div>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <form @submit.prevent="submit" class="space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Bidang/Karier</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        maxlength="255"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                        :aria-invalid="!!form.errors.name"
                        aria-describedby="name-error"
                    />
                    <p v-if="form.errors.name" id="name-error" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        maxlength="2000"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                        :aria-invalid="!!form.errors.description"
                        aria-describedby="description-error"
                    ></textarea>
                    <p v-if="form.errors.description" id="description-error" class="text-xs text-red-600 mt-1">{{ form.errors.description }}</p>
                </div>

                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 mb-1">Ikon (opsional)</label>
                    <input
                        id="icon"
                        v-model="form.icon"
                        type="text"
                        maxlength="255"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                        :aria-invalid="!!form.errors.icon"
                        aria-describedby="icon-error"
                    />
                    <p v-if="form.errors.icon" id="icon-error" class="text-xs text-red-600 mt-1">{{ form.errors.icon }}</p>
                </div>

                <div>
                    <label for="life_phase" class="block text-sm font-medium text-gray-700 mb-1">Berlaku untuk fase</label>
                    <select
                        id="life_phase"
                        v-model="form.life_phase"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                        :aria-invalid="!!form.errors.life_phase"
                        aria-describedby="life_phase-error"
                    >
                        <option v-for="(label, value) in lifePhaseOptions" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                    <p v-if="form.errors.life_phase" id="life_phase-error" class="text-xs text-red-600 mt-1">{{ form.errors.life_phase }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                    >
                        {{ form.processing ? 'Menyimpan…' : 'Simpan Perubahan' }}
                    </button>
                    <Link :href="route('admin.alternatives.index')" class="text-sm text-gray-500 hover:text-gray-700">
                        Batal
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
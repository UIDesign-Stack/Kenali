<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
    initial: { type: Object, default: () => ({}) },
    lifePhaseOptions: { type: Object, default: () => ({}) },
    method: { type: String, required: true, validator: (value) => ['post', 'put'].includes(value) },
    url: { type: String, required: true },
    submitLabel: { type: String, default: 'Simpan' },
});

const form = useForm({
    name: props.initial.name ?? '',
    description: props.initial.description ?? '',
    icon: props.initial.icon ?? '',
    life_phase: props.initial.life_phase ?? 'umum',
});

function submit() {
    form[props.method](props.url);
}

const inputClass = 'w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500';
</script>

<template>
    <div class="max-w-xl mx-auto p-6">
        <form @submit.prevent="submit" class="space-y-5">
            <FormField
                id="name"
                label="Nama Bidang/Karier"
                :error="form.errors.name"
                v-slot="{ invalid, describedby }"
            >
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    maxlength="255"
                    placeholder="Contoh: Sains & Teknik"
                    :class="inputClass"
                    :aria-invalid="invalid"
                    :aria-describedby="describedby"
                />
            </FormField>

            <FormField
                id="description"
                label="Deskripsi"
                :error="form.errors.description"
                v-slot="{ invalid, describedby }"
            >
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    maxlength="2000"
                    placeholder="Penjelasan singkat tentang bidang ini untuk ditampilkan ke user"
                    :class="inputClass"
                    :aria-invalid="invalid"
                    :aria-describedby="describedby"
                ></textarea>
            </FormField>

            <FormField
                id="icon"
                label="Ikon (opsional)"
                :error="form.errors.icon"
                v-slot="{ invalid, describedby }"
            >
                <input
                    id="icon"
                    v-model="form.icon"
                    type="text"
                    maxlength="50"
                    placeholder="Nama ikon, contoh: flask, palette, users"
                    :class="inputClass"
                    :aria-invalid="invalid"
                    :aria-describedby="describedby"
                />
            </FormField>

            <FormField
                id="life_phase"
                label="Berlaku untuk fase"
                :error="form.errors.life_phase"
                v-slot="{ invalid, describedby }"
            >
                <select
                    id="life_phase"
                    v-model="form.life_phase"
                    :class="inputClass"
                    :aria-invalid="invalid"
                    :aria-describedby="describedby"
                >
                    <option v-for="(label, value) in lifePhaseOptions" :key="value" :value="value">
                        {{ label }}
                    </option>
                </select>
            </FormField>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-5 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    {{ form.processing ? 'Menyimpan…' : submitLabel }}
                </button>
                <Link :href="route('admin.alternatives.index')" class="text-sm text-gray-500 hover:text-gray-700">
                    Batal
                </Link>
            </div>
        </form>
    </div>
</template>

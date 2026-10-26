<script setup>
import { computed } from 'vue';
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

const inputClass =
    'w-full rounded-xl border-gray-200 bg-gray-50 text-sm placeholder:text-gray-400 transition focus:border-indigo-400 focus:bg-white focus:ring-indigo-400';

const invalidClass = 'border-rose-400 bg-rose-50/40 focus:border-rose-400 focus:ring-rose-400';

// ---------------------------------------------------------------
// UI only: pratinjau kartu & penghitung karakter
// ---------------------------------------------------------------
const previewInitial = computed(() => (form.name || '?').trim().charAt(0).toUpperCase() || '?');
const previewPhase = computed(() => props.lifePhaseOptions[form.life_phase] ?? form.life_phase);
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

            <!-- ===== Form ===== -->
            <form
                @submit.prevent="submit"
                class="lg:col-span-3 rounded-3xl bg-white p-6 sm:p-8 ring-1 ring-gray-200/80 shadow-sm"
            >
                <div class="space-y-6">
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
                            :class="[inputClass, invalid ? invalidClass : '']"
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
                            rows="4"
                            maxlength="2000"
                            placeholder="Penjelasan singkat tentang bidang ini untuk ditampilkan ke user"
                            :class="[inputClass, 'resize-none', invalid ? invalidClass : '']"
                            :aria-invalid="invalid"
                            :aria-describedby="describedby"
                        ></textarea>
                        <p class="mt-1 text-right text-xs text-gray-400">{{ form.description.length }} / 2000</p>
                    </FormField>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
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
                                :class="[inputClass, invalid ? invalidClass : '']"
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
                                :class="[inputClass, invalid ? invalidClass : '']"
                                :aria-invalid="invalid"
                                :aria-describedby="describedby"
                            >
                                <option v-for="(label, value) in lifePhaseOptions" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </FormField>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                    <Link
                        :href="route('admin.alternatives.index')"
                        class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                        </svg>
                        {{ form.processing ? 'Menyimpan…' : submitLabel }}
                    </button>
                </div>
            </form>

            <!-- ===== Pratinjau ===== -->
            <aside class="lg:col-span-2">
                <div class="lg:sticky lg:top-24">
                    <p class="mb-3 text-sm font-semibold text-gray-700">Pratinjau kartu</p>

                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900 via-indigo-700 to-violet-600 p-6 text-white shadow-xl shadow-indigo-900/20">
                        <div class="pointer-events-none absolute -top-12 -right-8 h-40 w-40 rounded-full bg-rose-400/30 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-14 left-6 h-40 w-40 rounded-full bg-teal-300/25 blur-3xl"></div>

                        <div class="relative">
                            <div class="flex items-center gap-3">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-xl font-bold ring-1 ring-white/30 backdrop-blur">
                                    {{ previewInitial }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-lg font-bold leading-snug">
                                        {{ form.name || 'Nama bidang/karier' }}
                                    </p>
                                    <span class="mt-1 inline-flex rounded-full bg-white/15 px-2.5 py-0.5 text-[11px] font-medium text-indigo-50 ring-1 ring-white/20">
                                        {{ previewPhase }}
                                    </span>
                                </div>
                            </div>

                            <p class="mt-4 line-clamp-4 text-sm leading-relaxed text-indigo-100">
                                {{ form.description || 'Deskripsi singkat akan tampil di sini.' }}
                            </p>

                            <p v-if="form.icon" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1 text-xs text-indigo-100">
                                Ikon: <span class="font-semibold text-white">{{ form.icon }}</span>
                            </p>
                        </div>
                    </div>

                    <p class="mt-3 text-xs leading-relaxed text-gray-400">
                        Tampilan ini hanya contoh dan ikut berubah saat Anda mengetik.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</template>
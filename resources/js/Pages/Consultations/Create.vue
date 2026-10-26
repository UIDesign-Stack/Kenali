<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StarRating from '@/Components/StarRating.vue';

const props = defineProps({
    psychologists: { type: Array, required: true },
    completedSessions: { type: Array, required: true },

    lifePhaseOptions: { type: Object, default: () => ({}) },
});

const form = useForm({
    psychologist_profile_id: '',
    test_session_id: '',
    type: 'chat',
    notes: '',
});

function topAlternativeName(session) {
    const top = session.result?.topDetail ?? session.result?.top_detail;
    return top?.alternative?.name ?? null;
}

function lifePhaseLabel(session) {
    return props.lifePhaseOptions[session.life_phase] ?? session.life_phase;
}

function submit() {
    form.post(route('consultations.store'));
}

const avatarColors = [
    'bg-indigo-100 text-indigo-700',
    'bg-teal-100 text-teal-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-sky-100 text-sky-700',
    'bg-violet-100 text-violet-700',
];

function avatarColor(index) {
    return avatarColors[index % avatarColors.length];
}

function initials(name) {
    if (!name) return '?';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() ?? '')
        .join('');
}

const baseInput =
    'w-full rounded-xl border-gray-200 bg-gray-50 text-sm placeholder:text-gray-400 transition focus:border-indigo-400 focus:bg-white focus:ring-indigo-400';
const errorInput = 'border-rose-400 bg-rose-50/40 focus:border-rose-400 focus:ring-rose-400';

function inputClass(field) {
    return [baseInput, form.errors[field] ? errorInput : ''];
}

const typeOptions = [
    {
        value: 'chat',
        label: 'Chat',
        on: 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/20',
        icon: 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z',
        iconColor: 'text-indigo-500',
    },
    {
        value: 'tatap_muka',
        label: 'Tatap Muka Langsung',
        on: 'border-teal-500 bg-teal-50 ring-2 ring-teal-500/20',
        icon: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        iconColor: 'text-teal-500',
    },
];
</script>

<template>
    <Head title="Ajukan Konsultasi" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Ajukan Konsultasi</h1>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-5">

                <!-- ===== Kiri: pilih psikolog ===== -->
                <fieldset class="lg:col-span-3">
                    <legend class="mb-3 text-base font-semibold text-gray-800">Pilih Psikolog</legend>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label
                            v-for="(psych, index) in psychologists"
                            :key="psych.id"
                            class="relative flex flex-col rounded-3xl border-2 p-5 transition duration-200 focus-within:ring-2 focus-within:ring-indigo-400 focus-within:ring-offset-2 motion-reduce:transition-none"
                            :class="[
                                form.psychologist_profile_id === psych.id
                                    ? 'border-indigo-500 bg-indigo-50/60 ring-4 ring-indigo-500/10'
                                    : 'border-transparent bg-white shadow-sm ring-1 ring-gray-200/80',
                                psych.is_available === false
                                    ? 'cursor-not-allowed opacity-50'
                                    : 'cursor-pointer hover:-translate-y-0.5 hover:shadow-md motion-reduce:hover:translate-y-0',
                            ]"
                        >
                            <input
                                type="radio"
                                v-model="form.psychologist_profile_id"
                                name="psychologist_profile_id"
                                :value="psych.id"
                                :disabled="psych.is_available === false"
                                class="sr-only"
                            />

                            <span
                                v-if="form.psychologist_profile_id === psych.id"
                                class="absolute right-4 top-4 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-500 text-white"
                                aria-hidden="true"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>

                            <div class="flex items-center gap-3">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="avatarColor(index)">
                                    {{ initials(psych.user.name) }}
                                </span>
                                <div class="min-w-0 pr-6">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ psych.user.name }}</p>
                                    <p v-if="psych.is_available === false" class="text-xs text-gray-400">
                                        (sedang tidak tersedia)
                                    </p>
                                </div>
                            </div>

                            <p class="mt-3 text-xs text-gray-500">{{ psych.specialization }}</p>

                            <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <template v-if="psych.rating_count > 0">
                                    <StarRating :model-value="Number(psych.rating_avg)" readonly size="text-sm" />
                                    <span class="text-xs text-gray-500">
                                        {{ Number(psych.rating_avg).toFixed(1) }} ({{ psych.rating_count }} ulasan)
                                    </span>
                                    <a
                                        :href="route('psychologists.reviews', psych.id)"
                                        target="_blank"
                                        rel="noopener"
                                        @click.stop
                                        class="text-xs font-medium text-indigo-600 hover:underline"
                                    >
                                        Lihat ulasan
                                    </a>
                                </template>
                                <span v-else class="text-xs text-gray-400">Belum ada ulasan</span>
                            </div>
                        </label>
                    </div>

                    <p
                        v-if="psychologists.length === 0"
                        class="rounded-3xl border border-dashed border-gray-300 bg-white py-12 text-center text-sm text-gray-400"
                    >
                        Belum ada psikolog yang tersedia saat ini.
                    </p>
                    <p v-if="form.errors.psychologist_profile_id" class="mt-2 text-xs text-rose-600" role="alert">
                        {{ form.errors.psychologist_profile_id }}
                    </p>
                </fieldset>

                <!-- ===== Kanan: detail konsultasi ===== -->
                <aside class="lg:col-span-2">
                    <div class="space-y-6 rounded-3xl bg-white p-6 ring-1 ring-gray-200/80 shadow-sm sm:p-8 lg:sticky lg:top-24">

                        <!-- Jenis -->
                        <fieldset>
                            <legend class="mb-2 block text-sm font-medium text-gray-700">Jenis Konsultasi</legend>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    v-for="t in typeOptions"
                                    :key="t.value"
                                    class="flex cursor-pointer flex-col items-center gap-2 rounded-2xl border p-4 text-center transition focus-within:ring-2 focus-within:ring-indigo-400"
                                    :class="form.type === t.value ? t.on : 'border-gray-200 bg-white hover:bg-gray-50'"
                                >
                                    <input type="radio" v-model="form.type" name="type" :value="t.value" class="sr-only" />
                                    <svg class="h-6 w-6" :class="form.type === t.value ? t.iconColor : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="t.icon" />
                                    </svg>
                                    <span class="text-xs font-semibold" :class="form.type === t.value ? 'text-gray-900' : 'text-gray-500'">{{ t.label }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.type" class="mt-1 text-xs text-rose-600" role="alert">{{ form.errors.type }}</p>
                        </fieldset>

                        <!-- Hasil tes -->
                        <div v-if="completedSessions.length > 0">
                            <label for="test_session_id" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Kaitkan dengan hasil tes (opsional)
                            </label>
                            <select
                                id="test_session_id"
                                v-model="form.test_session_id"
                                aria-describedby="test-session-note"
                                :class="inputClass('test_session_id')"
                            >
                                <option value="">Tidak dikaitkan</option>
                                <option v-for="session in completedSessions" :key="session.id" :value="session.id">
                                    {{ lifePhaseLabel(session) }} — {{ topAlternativeName(session) ?? 'hasil belum ada' }}
                                </option>
                            </select>
                            <p id="test-session-note" class="mt-1.5 text-xs text-gray-500">
                                Kalau dipilih, psikolog yang kamu pilih dapat melihat hasil tes ini.
                            </p>
                            <p v-if="form.errors.test_session_id" class="mt-1 text-xs text-rose-600" role="alert">
                                {{ form.errors.test_session_id }}
                            </p>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label for="notes" class="mb-1.5 block text-sm font-medium text-gray-700">
                                Ceritakan singkat kebutuhanmu
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                maxlength="1000"
                                aria-describedby="notes-note"
                                placeholder="Contoh: Saya masih bingung memilih antara dua bidang dari hasil tes saya."
                                :class="[inputClass('notes'), 'resize-none']"
                            ></textarea>
                            <p id="notes-note" class="mt-1.5 text-xs text-gray-500">
                                Catatan ini dibaca psikolog yang kamu pilih. Tulis seperlunya saja.
                            </p>
                            <div class="mt-1 flex items-center justify-between">
                                <p v-if="form.errors.notes" class="text-xs text-rose-600" role="alert">{{ form.errors.notes }}</p>
                                <p class="ml-auto text-[10px] text-gray-400">{{ form.notes.length }}/1000</p>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing || !form.psychologist_profile_id"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                            </svg>
                            {{ form.processing ? 'Mengajukan…' : 'Ajukan Konsultasi' }}
                        </button>
                    </div>
                </aside>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

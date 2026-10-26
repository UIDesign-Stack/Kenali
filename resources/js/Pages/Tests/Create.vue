<script setup>
import { computed } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    inProgressSession: { type: Object, default: null },
    defaultLifePhase: { type: String, default: null },

    lifePhaseOptions: { type: Object, default: () => ({}) },
});

const initialLifePhase = props.defaultLifePhase && props.lifePhaseOptions[props.defaultLifePhase]
    ? props.defaultLifePhase
    : '';

const form = useForm({
    life_phase: initialLifePhase,
});

const descriptions = {
    siswa: 'Masih sekolah (SMP/SMA) dan sedang menimbang jurusan kuliah',
    mahasiswa: 'Sedang kuliah atau baru lulus, mencari arah karier',
    pekerja: 'Sudah bekerja dan mempertimbangkan pengembangan atau perubahan karier',
};

const options = computed(() =>
    Object.entries(props.lifePhaseOptions).map(([value, label]) => ({
        value,
        label,
        desc: descriptions[value] ?? '',
    }))
);

function submit() {
    if (form.processing) return;

    if (props.inProgressSession) {
        const confirmed = confirm(
            'Kamu masih punya tes yang belum selesai. Yakin ingin mulai tes baru?'
        );
        if (!confirmed) return;
    }

    form.post(route('tests.store'));
}

const iconPaths = {
    siswa: 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5',
    mahasiswa: 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
    pekerja: 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z',
};

const fallbackIcon =
    'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z';

const palettes = {
    siswa: {
        tile: 'bg-indigo-500 text-white shadow-indigo-500/30',
        on: 'border-indigo-500 bg-indigo-50 ring-4 ring-indigo-500/15',
        check: 'bg-indigo-500',
    },
    mahasiswa: {
        tile: 'bg-teal-500 text-white shadow-teal-500/30',
        on: 'border-teal-500 bg-teal-50 ring-4 ring-teal-500/15',
        check: 'bg-teal-500',
    },
    pekerja: {
        tile: 'bg-amber-400 text-amber-950 shadow-amber-400/40',
        on: 'border-amber-400 bg-amber-50 ring-4 ring-amber-400/20',
        check: 'bg-amber-400',
    },
};

const fallbackPalettes = [
    {
        tile: 'bg-rose-400 text-white shadow-rose-400/40',
        on: 'border-rose-400 bg-rose-50 ring-4 ring-rose-400/15',
        check: 'bg-rose-400',
    },
    {
        tile: 'bg-violet-500 text-white shadow-violet-500/30',
        on: 'border-violet-500 bg-violet-50 ring-4 ring-violet-500/15',
        check: 'bg-violet-500',
    },
];

function paletteFor(option, index) {
    return palettes[option.value] ?? fallbackPalettes[index % fallbackPalettes.length];
}

function iconFor(option) {
    return iconPaths[option.value] ?? fallbackIcon;
}
</script>

<template>
    <Head title="Mulai Tes" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Mulai Tes Minat, Bakat & Kepribadian</h1>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

            <!-- Tes yang belum selesai -->
            <div
                v-if="inProgressSession"
                class="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-amber-950">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    </span>
                    <p class="text-sm text-amber-900">
                        Kamu punya tes yang belum selesai. Lanjutkan dulu, atau mulai tes baru di bawah ini.
                    </p>
                </div>
                <Link
                    :href="route('tests.show', inProgressSession.id)"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-semibold text-amber-950 shadow-sm transition hover:bg-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2"
                >
                    Lanjutkan tes sebelumnya →
                </Link>
            </div>

            <!-- Pengantar -->
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-violet-600 to-rose-500 px-6 py-8 text-white shadow-xl shadow-violet-900/20 sm:px-8">
                <div class="pointer-events-none absolute -top-16 -right-10 h-56 w-56 rounded-full bg-amber-300/40 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 left-1/4 h-56 w-56 rounded-full bg-teal-300/30 blur-3xl"></div>
                <div class="relative max-w-2xl">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Seperti apa kondisimu sekarang?</h2>
                    <p id="life-phase-desc" class="mt-3 text-sm leading-relaxed text-indigo-50">
                        Pilih kondisi yang paling menggambarkan dirimu sekarang, supaya rekomendasi
                        yang kamu dapat nanti lebih relevan.
                    </p>
                </div>
            </section>

            <form @submit.prevent="submit" class="space-y-5">
                <fieldset aria-describedby="life-phase-desc">
                    <legend class="sr-only">Pilih fase hidupmu saat ini</legend>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <label
                            v-for="(option, index) in options"
                            :key="option.value"
                            class="relative flex cursor-pointer flex-col rounded-3xl border-2 p-6 transition duration-200 focus-within:ring-2 focus-within:ring-indigo-400 focus-within:ring-offset-2 motion-reduce:transition-none"
                            :class="form.life_phase === option.value
                                ? paletteFor(option, index).on
                                : 'border-transparent bg-white shadow-sm ring-1 ring-gray-200/80 hover:-translate-y-0.5 hover:shadow-md motion-reduce:hover:translate-y-0'"
                        >
                            <input
                                type="radio"
                                v-model="form.life_phase"
                                name="life_phase"
                                :value="option.value"
                                class="sr-only"
                            />

                            <!-- Tanda terpilih -->
                            <span
                                v-if="form.life_phase === option.value"
                                class="absolute right-4 top-4 flex h-7 w-7 items-center justify-center rounded-full text-white"
                                :class="paletteFor(option, index).check"
                                aria-hidden="true"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>

                            <span
                                class="flex h-14 w-14 items-center justify-center rounded-2xl shadow-lg"
                                :class="paletteFor(option, index).tile"
                            >
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="iconFor(option)" />
                                </svg>
                            </span>

                            <p class="mt-5 text-lg font-bold text-gray-900">{{ option.label }}</p>
                            <p class="mt-1.5 text-sm leading-relaxed text-gray-500">{{ option.desc }}</p>
                        </label>
                    </div>
                </fieldset>

                <p v-if="form.errors.life_phase" class="text-sm text-rose-600" role="alert">{{ form.errors.life_phase }}</p>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing || !form.life_phase"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none sm:w-auto"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin motion-reduce:animate-none" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                        </svg>
                        {{ form.processing ? 'Memulai…' : 'Mulai Tes' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

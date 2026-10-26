<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AhpMatrixInput from '@/Components/AhpMatrixInput.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    items: { type: Array, required: true },
});

const hasSaved = ref(false);

function handleSaved() {
    hasSaved.value = true;
}

// UI only: palet kartu sub-kriteria (class ditulis penuh agar terbaca Tailwind)
const cardPalettes = [
    { tile: 'bg-indigo-100 text-indigo-700', ring: 'hover:ring-indigo-300', link: 'text-indigo-600', glow: 'bg-indigo-400/20' },
    { tile: 'bg-teal-100 text-teal-700', ring: 'hover:ring-teal-300', link: 'text-teal-600', glow: 'bg-teal-400/20' },
    { tile: 'bg-amber-100 text-amber-700', ring: 'hover:ring-amber-300', link: 'text-amber-600', glow: 'bg-amber-400/20' },
    { tile: 'bg-rose-100 text-rose-700', ring: 'hover:ring-rose-300', link: 'text-rose-600', glow: 'bg-rose-400/20' },
    { tile: 'bg-sky-100 text-sky-700', ring: 'hover:ring-sky-300', link: 'text-sky-600', glow: 'bg-sky-400/20' },
    { tile: 'bg-violet-100 text-violet-700', ring: 'hover:ring-violet-300', link: 'text-violet-600', glow: 'bg-violet-400/20' },
];

function palette(index) {
    return cardPalettes[index % cardPalettes.length];
}

function initial(name) {
    return (name ?? '?').trim().charAt(0).toUpperCase();
}
</script>

<template>
    <Head title="Bobot AHP — Kriteria Utama" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">
                Bobot AHP — Kriteria Utama
            </h1>
        </template>

        <!-- Peringatan: kriteria kurang dari 2 -->
        <div v-if="items.length < 2" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <p>
                    Minimal butuh 2 kriteria utama untuk membuat matriks perbandingan.
                    Saat ini baru ada {{ items.length }} kriteria terdaftar.
                </p>
            </div>
        </div>

        <AhpMatrixInput
            v-else
            :items="items"
            submit-url="/admin/ahp/criteria"
            id-field="criteria_ids"
            @saved="handleSaved"
        />

        <!-- Lanjut ke sub-kriteria -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <section class="rounded-3xl bg-white p-6 sm:p-8 ring-1 ring-gray-200/80 shadow-sm">

                <!-- Notifikasi tersimpan -->
                <div
                    v-if="hasSaved"
                    class="mb-6 flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200"
                    role="status"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                    <span>Bobot kriteria utama tersimpan. Lanjutkan atur sub-kriteria di bawah.</span>
                </div>

                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-600">
                            Langkah berikutnya
                        </span>
                        <h3 class="mt-2 text-lg font-semibold text-gray-800">
                            Lanjut atur bobot sub-kriteria
                        </h3>
                    </div>
                </div>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-500">
                    Setelah bobot kriteria utama tersimpan konsisten, atur juga bobot
                    di dalam masing-masing kriteria berikut.
                </p>

                <div v-if="items.length === 0" class="mt-6 flex flex-col items-center rounded-2xl border border-dashed border-gray-200 py-10 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-50 text-gray-400">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm text-gray-500">Belum ada kriteria utama yang terdaftar.</p>
                </div>

                <nav v-else class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Daftar kriteria untuk atur sub-kriteria">
                    <Link
                        v-for="(item, index) in items"
                        :key="item.id"
                        :href="route('admin.ahp.sub-criteria.index', item.id)"
                        class="group relative overflow-hidden rounded-2xl bg-gray-50 p-5 ring-1 ring-gray-200/80 transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 motion-reduce:transition-none motion-reduce:hover:translate-y-0"
                        :class="palette(index).ring"
                    >
                        <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full blur-2xl" :class="palette(index).glow"></div>

                        <div class="relative flex items-center gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-lg font-bold" :class="palette(index).tile">
                                {{ initial(item.name) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold text-gray-800">{{ item.name }}</p>
                                <p class="mt-0.5 inline-flex items-center gap-1 text-xs font-medium" :class="palette(index).link">
                                    Atur Sub-kriteria
                                    <span class="transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0">→</span>
                                </p>
                            </div>
                        </div>
                    </Link>
                </nav>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    inProgressSession: { type: Object, default: null },
    defaultLifePhase: { type: String, default: null },
    // Dikirim dari TestController::create() via LifePhase::assignableToUserOptions().
    // Hanya berisi { value: label }. Deskripsi tetap di frontend (murni teks
    // UX, bukan nilai bisnis), supaya tidak perlu menambah field non-enum ke
    // backend hanya untuk copy tampilan.
    lifePhaseOptions: { type: Object, default: () => ({}) },
});

const form = useForm({
    life_phase: props.defaultLifePhase ?? '',
});

// Deskripsi per opsi -- keyed by value yang SAMA dengan lifePhaseOptions dari
// backend, supaya value/label tetap satu sumber kebenaran, cuma desc yang
// lokal di sini.
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
    if (props.inProgressSession) {
        const confirmed = confirm(
            'Kamu masih punya tes yang belum selesai. Yakin ingin mulai tes baru?'
        );
        if (!confirmed) return;
    }

    form.post(route('tests.store'));
}
</script>

<template>
    <Head title="Mulai Tes" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Mulai Tes Minat, Bakat & Kepribadian</h1>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <div v-if="inProgressSession" class="mb-6 p-4 rounded-md bg-amber-50 border border-amber-200">
                <p class="text-sm text-amber-800">
                    Kamu punya tes yang belum selesai. Lanjutkan dulu, atau mulai tes baru di bawah ini.
                </p>
                <Link
                    :href="route('tests.show', inProgressSession.id)"
                    class="inline-block mt-2 text-sm font-medium text-amber-700 underline"
                >
                    Lanjutkan tes sebelumnya →
                </Link>
            </div>

            <p id="life-phase-desc" class="text-sm text-gray-500 mb-6">
                Pilih kondisi yang paling menggambarkan dirimu sekarang, supaya rekomendasi
                yang kamu dapat nanti lebih relevan.
            </p>

            <form @submit.prevent="submit" class="space-y-3">
                <fieldset aria-describedby="life-phase-desc">
                    <legend class="sr-only">Pilih fase hidupmu saat ini</legend>

                    <label
                        v-for="option in options"
                        :key="option.value"
                        class="flex items-start gap-3 p-4 border rounded-lg cursor-pointer transition mb-3"
                        :class="form.life_phase === option.value
                            ? 'border-teal-500 bg-teal-50'
                            : 'border-gray-200 hover:border-teal-300'"
                    >
                        <input
                            type="radio"
                            v-model="form.life_phase"
                            name="life_phase"
                            :value="option.value"
                            class="mt-1 text-teal-600 focus:ring-teal-500"
                        />
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ option.label }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ option.desc }}</p>
                        </div>
                    </label>
                </fieldset>

                <p v-if="form.errors.life_phase" class="text-xs text-red-600">{{ form.errors.life_phase }}</p>

                <button
                    type="submit"
                    :disabled="form.processing || !form.life_phase"
                    class="w-full mt-4 px-5 py-3 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    {{ form.processing ? 'Memulai…' : 'Mulai Tes' }}
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
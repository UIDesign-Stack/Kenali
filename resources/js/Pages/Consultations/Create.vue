<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    psychologists: { type: Array, required: true },
    completedSessions: { type: Array, required: true },
    // Dikirim dari ConsultationController::create().
    lifePhaseOptions: { type: Object, default: () => ({}) },
});

const form = useForm({
    psychologist_profile_id: '',
    test_session_id: '',
    type: 'chat',
    notes: '',
});

// Backend mengirim result.topDetail (rank teratas), BUKAN result.details[0].
// Relasi `details` sengaja di-unset di ConsultationController::create()
// setelah topDetail dihitung, jadi jangan akses session.result.details lagi.
function topAlternativeName(session) {
    return session.result?.topDetail?.alternative?.name ?? null;
}

function lifePhaseLabel(session) {
    return props.lifePhaseOptions[session.life_phase] ?? session.life_phase;
}

function submit() {
    form.post(route('consultations.store'));
}
</script>

<template>
    <Head title="Ajukan Konsultasi" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-800">Ajukan Konsultasi</h1>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <form @submit.prevent="submit" class="space-y-5">
                <fieldset>
                    <legend class="block text-sm font-medium text-gray-700 mb-2">Pilih Psikolog</legend>
                    <div class="space-y-2">
                        <label
                            v-for="psych in psychologists"
                            :key="psych.id"
                            class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer"
                            :class="[
                                form.psychologist_profile_id === psych.id
                                    ? 'border-teal-500 bg-teal-50'
                                    : 'border-gray-200 hover:border-teal-300',
                                psych.is_available === false ? 'opacity-50' : '',
                            ]"
                        >
                            <input
                                type="radio"
                                v-model="form.psychologist_profile_id"
                                name="psychologist_profile_id"
                                :value="psych.id"
                                :disabled="psych.is_available === false"
                                class="text-teal-600"
                            />
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    {{ psych.user.name }}
                                    <span v-if="psych.is_available === false" class="text-xs text-gray-400 font-normal">
                                        (sedang tidak tersedia)
                                    </span>
                                </p>
                                <p class="text-xs text-gray-500">{{ psych.specialization }}</p>
                            </div>
                        </label>
                    </div>
                    <p v-if="psychologists.length === 0" class="text-sm text-gray-400">
                        Belum ada psikolog yang tersedia saat ini.
                    </p>
                    <p v-if="form.errors.psychologist_profile_id" class="text-xs text-red-600 mt-1">
                        {{ form.errors.psychologist_profile_id }}
                    </p>
                </fieldset>

                <div v-if="completedSessions.length > 0">
                    <label for="test_session_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Kaitkan dengan hasil tes (opsional)
                    </label>
                    <select
                        id="test_session_id"
                        v-model="form.test_session_id"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    >
                        <option value="">Tidak dikaitkan</option>
                        <option v-for="session in completedSessions" :key="session.id" :value="session.id">
                            {{ lifePhaseLabel(session) }} — {{ topAlternativeName(session) ?? 'hasil belum ada' }}
                        </option>
                    </select>
                    <p v-if="form.errors.test_session_id" class="text-xs text-red-600 mt-1">
                        {{ form.errors.test_session_id }}
                    </p>
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Jenis Konsultasi</label>
                    <select
                        id="type"
                        v-model="form.type"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    >
                        <option value="chat">Chat</option>
                        <option value="tatap_muka">Tatap Muka Langsung</option>
                    </select>
                    <p v-if="form.errors.type" class="text-xs text-red-600 mt-1">{{ form.errors.type }}</p>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        Ceritakan singkat kebutuhanmu
                    </label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="4"
                        maxlength="1000"
                        placeholder="Contoh: Saya masih bingung memilih antara dua bidang dari hasil tes saya."
                        class="w-full rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    ></textarea>
                    <div class="flex justify-between items-center mt-1">
                        <p v-if="form.errors.notes" class="text-xs text-red-600">{{ form.errors.notes }}</p>
                        <p class="text-[10px] text-gray-400 ml-auto">{{ form.notes.length }}/1000</p>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing || !form.psychologist_profile_id"
                    class="w-full px-5 py-3 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                >
                    {{ form.processing ? 'Mengajukan…' : 'Ajukan Konsultasi' }}
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
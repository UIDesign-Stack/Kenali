<script setup>
import { ref } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    consultation: { type: Object, required: true },
});

const newMessage = ref('');
const sending = ref(false);

const scheduleForm = useForm({
    status: 'scheduled',
    scheduled_at: '',
    notes: '',
    cancelled_reason: '',
});

function acceptAndSchedule() {
    scheduleForm.status = 'scheduled';
    scheduleForm.patch(route('psikolog.consultations.update-status', props.consultation.id), {
        preserveScroll: true,
    });
}

function markCompleted() {
    router.patch(route('psikolog.consultations.update-status', props.consultation.id), {
        status: 'completed',
    }, { preserveScroll: true });
}

function cancelConsultation() {
    const reason = prompt('Alasan pembatalan:');
    if (!reason) return;

    router.patch(route('psikolog.consultations.update-status', props.consultation.id), {
        status: 'cancelled',
        cancelled_reason: reason,
    }, { preserveScroll: true });
}

function sendMessage() {
    if (!newMessage.value.trim()) return;
    sending.value = true;

    router.post(route('psikolog.consultations.messages.send', props.consultation.id), {
        message: newMessage.value,
    }, {
        preserveScroll: true,
        onSuccess: () => (newMessage.value = ''),
        onFinish: () => (sending.value = false),
    });
}

function topAlternative() {
    return props.consultation.test_session?.result?.details?.[0]?.alternative?.name ?? null;
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('psikolog.consultations.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Konsultasi
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">{{ consultation.user.name }}</h1>
            </div>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <div v-if="topAlternative()" class="mb-4 p-3 rounded-md bg-teal-50 text-sm text-teal-700">
                Rekomendasi dari hasil tes: <strong>{{ topAlternative() }}</strong>
            </div>

            <div v-if="consultation.notes" class="mb-4 p-3 rounded-md bg-gray-50 text-sm text-gray-600">
                "{{ consultation.notes }}"
            </div>

            <!-- Aksi kalau masih pending -->
            <div v-if="consultation.status === 'pending'" class="p-4 border border-amber-200 bg-amber-50 rounded-lg mb-4">
                <p class="text-sm text-amber-700 mb-3">Terima permintaan ini?</p>
                <input
                    v-model="scheduleForm.scheduled_at"
                    type="datetime-local"
                    class="w-full mb-3 rounded-md border-gray-300 text-sm"
                />
                <div class="flex gap-2">
                    <button
                        @click="acceptAndSchedule"
                        :disabled="scheduleForm.processing || !scheduleForm.scheduled_at"
                        class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40"
                    >
                        Terima & Jadwalkan
                    </button>
                    <button
                        @click="cancelConsultation"
                        class="px-4 py-2 rounded-md bg-gray-100 text-gray-600 text-sm font-medium"
                    >
                        Tolak
                    </button>
                </div>
            </div>

            <!-- Chat kalau sudah scheduled -->
            <template v-if="consultation.status === 'scheduled'">
                <div class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                    <div
                        v-for="msg in consultation.messages"
                        :key="msg.id"
                        class="max-w-[80%] p-3 rounded-lg text-sm"
                        :class="msg.sender.id === $page.props.auth.user.id
                            ? 'ml-auto bg-teal-600 text-white'
                            : 'bg-gray-100 text-gray-700'"
                    >
                        {{ msg.message }}
                    </div>
                </div>

                <div class="flex gap-2 mb-4">
                    <input
                        v-model="newMessage"
                        @keyup.enter="sendMessage"
                        type="text"
                        placeholder="Tulis pesan…"
                        class="flex-1 rounded-md border-gray-300 text-sm"
                    />
                    <button @click="sendMessage" :disabled="sending" class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm">
                        Kirim
                    </button>
                </div>

                <button @click="markCompleted" class="text-xs text-gray-500 hover:text-green-600">
                    Tandai selesai
                </button>
            </template>

            <div v-if="consultation.status === 'completed'" class="text-center text-sm text-green-600 py-6">
                Konsultasi ini sudah selesai.
            </div>
            <div v-if="consultation.status === 'cancelled'" class="text-center text-sm text-gray-500 py-6">
                Dibatalkan: {{ consultation.cancelled_reason }}
            </div>
        </div>
    </AuthenticatedLayout>
</template>
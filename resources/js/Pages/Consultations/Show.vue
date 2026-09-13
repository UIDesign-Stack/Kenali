<script setup>
import { ref, nextTick, onMounted, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    consultation: { type: Object, required: true },
});

const page = usePage();
const newMessage = ref('');
const sending = ref(false);
const sendError = ref(null);
const messagesContainer = ref(null);

const statusLabel = {
    pending: 'Menunggu respon psikolog',
    scheduled: 'Terjadwal',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

onMounted(scrollToBottom);
watch(() => props.consultation.messages?.length, scrollToBottom);

function sendMessage() {
    if (sending.value || !newMessage.value.trim()) return;

    sending.value = true;
    sendError.value = null;

    router.post(route('consultations.messages.send', props.consultation.id), {
        message: newMessage.value,
    }, {
        preserveScroll: true,
        only: ['consultation'],
        onSuccess: () => (newMessage.value = ''),
        onError: (errors) => {
            sendError.value = errors.message ?? 'Pesan gagal terkirim. Coba lagi.';
        },
        onFinish: () => (sending.value = false),
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('consultations.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Konsultasi
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    {{ consultation.psychologist_profile?.user?.name ?? 'Psikolog' }}
                </h1>
            </div>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <div class="mb-4 p-3 rounded-md bg-gray-50 text-sm text-gray-600 text-center">
                {{ statusLabel[consultation.status] ?? consultation.status }}
            </div>

            <div v-if="consultation.status === 'pending'" class="p-6 border border-amber-200 bg-amber-50 rounded-lg text-center text-sm text-amber-700">
                Menunggu psikolog menerima permintaan konsultasimu.
            </div>

            <template v-else>
                <div ref="messagesContainer" class="space-y-3 mb-4 max-h-96 overflow-y-auto scroll-smooth">
                    <div
                        v-for="msg in consultation.messages"
                        :key="msg.id"
                        class="max-w-[80%] p-3 rounded-lg text-sm"
                        :class="msg.sender?.id === page.props.auth.user.id
                            ? 'ml-auto bg-teal-600 text-white'
                            : 'bg-gray-100 text-gray-700'"
                    >
                        {{ msg.message }}
                    </div>
                    <p v-if="consultation.messages.length === 0" class="text-xs text-gray-400 text-center py-6">
                        Belum ada pesan. Mulai percakapan di bawah.
                    </p>
                </div>

                <p v-if="sendError" class="text-xs text-red-600 mb-2">{{ sendError }}</p>

                <div v-if="consultation.status !== 'completed' && consultation.status !== 'cancelled'" class="flex gap-2">
                    <input
                        v-model="newMessage"
                        @keyup.enter="sendMessage"
                        type="text"
                        placeholder="Tulis pesan…"
                        :disabled="sending"
                        class="flex-1 rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500 disabled:opacity-60"
                    />
                    <button
                        @click="sendMessage"
                        :disabled="sending || !newMessage.trim()"
                        class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                    >
                        {{ sending ? '...' : 'Kirim' }}
                    </button>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
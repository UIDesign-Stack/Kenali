<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const page = usePage();

const props = defineProps({
    consultation: { type: Object, required: true },
});

const messages = ref([...props.consultation.messages]);
const newMessage = ref('');
const sending = ref(false);
const sendError = ref(null);
const messagesContainer = ref(null);
const otherPartyTyping = ref(false);

let echoChannel = null;
let typingHideTimeout = null;
let lastWhisperAt = 0;

const statusLabel = {
    pending: 'Menunggu respon psikolog',
    scheduled: 'Terjadwal',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

const typeLabel = {
    chat: 'Chat',
    tatap_muka: 'Tatap Muka Langsung',
};

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

function pushIfNew(message) {
    // Dedupe: kalau pesan ini sudah ada (misal dari respons kirim sendiri),
    // jangan dobel-tambahkan saat broadcast realtime juga datang.
    if (!messages.value.some((m) => m.id === message.id)) {
        messages.value.push(message);
        scrollToBottom();
    }
    // Pesan sungguhan masuk -- indikator "sedang menulis" jadi tidak relevan lagi.
    otherPartyTyping.value = false;
    clearTimeout(typingHideTimeout);
}

// Dipanggil tiap kali user mengetik di kotak pesan. Di-throttle supaya
// tidak mengirim whisper di setiap keystroke (client events punya rate
// limit di sisi server) -- cukup kirim maksimal sekali tiap 2 detik
// selama user aktif mengetik.
function handleTyping() {
    if (!echoChannel || props.consultation.status !== 'scheduled') return;

    const now = Date.now();
    if (now - lastWhisperAt < 2000) return;
    lastWhisperAt = now;

    echoChannel.whisper('typing', {});
}

async function sendMessage() {
    if (!newMessage.value.trim()) return;

    sending.value = true;
    sendError.value = null;

    try {
        const res = await fetch(route('consultations.messages.send', props.consultation.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ message: newMessage.value }),
        });

        if (res.ok) {
            const data = await res.json();
            pushIfNew(data.data);
            newMessage.value = '';
            return;
        }

        // res tidak ok -- tampilkan pesan errornya, jangan diam saja.
        const errorData = await res.json().catch(() => null);
        sendError.value = errorData?.message
            ?? errorData?.errors?.message?.[0]
            ?? 'Pesan gagal terkirim. Coba lagi.';
    } catch (e) {
        console.error('Gagal kirim pesan:', e);
        sendError.value = 'Terjadi kesalahan jaringan. Coba lagi.';
    } finally {
        sending.value = false;
    }
}

const channelName = `consultation.${props.consultation.id}`;

onMounted(() => {
    scrollToBottom();

    if (props.consultation.status !== 'scheduled') return;

    echoChannel = window.Echo.private(channelName);

    echoChannel
        .listen('.message.sent', (e) => {
            pushIfNew(e);
        })
        .listenForWhisper('typing', () => {
            otherPartyTyping.value = true;
            clearTimeout(typingHideTimeout);
            // Sembunyikan lagi kalau tidak ada whisper baru dalam 3 detik
            // (nandain lawan bicara berhenti mengetik / diam).
            typingHideTimeout = setTimeout(() => {
                otherPartyTyping.value = false;
            }, 3000);
        });
});

onBeforeUnmount(() => {
    clearTimeout(typingHideTimeout);
    window.Echo.leave(channelName);
});
</script>

<template>
    <Head :title="consultation.psychologist_profile.user.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('consultations.index')" class="text-sm text-gray-400 hover:text-gray-600">
                    ← Konsultasi
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    {{ consultation.psychologist_profile.user.name }}
                </h1>
            </div>
        </template>

        <div class="max-w-xl mx-auto p-6">
            <div class="mb-4 p-3 rounded-md bg-gray-50 text-sm text-gray-600 text-center">
                {{ statusLabel[consultation.status] ?? consultation.status }} · {{ typeLabel[consultation.type] ?? consultation.type }}
            </div>

            <div
                v-if="consultation.type === 'tatap_muka' && consultation.status === 'scheduled'"
                class="mb-4 p-3 rounded-md bg-teal-50 text-sm text-teal-700"
            >
                📍 Pertemuan tatap muka langsung. Detail lokasi & waktu bisa dilihat di
                catatan psikolog atau tanyakan lewat chat.
            </div>

            <div v-if="consultation.notes" class="mb-4 p-3 rounded-md bg-gray-50 text-sm text-gray-600">
                "{{ consultation.notes }}"
            </div>

            <div v-if="consultation.status === 'pending'" class="p-6 border border-amber-200 bg-amber-50 rounded-lg text-center text-sm text-amber-700">
                Menunggu psikolog menerima permintaan konsultasimu.
            </div>

            <div v-else-if="consultation.status === 'cancelled'" class="p-6 border border-gray-200 bg-gray-50 rounded-lg text-center">
                <p class="text-sm text-gray-600 mb-2">Konsultasi ini dibatalkan.</p>
                <p v-if="consultation.cancelled_reason" class="text-sm text-gray-500 italic">
                    "{{ consultation.cancelled_reason }}"
                </p>
            </div>

            <template v-else>
                <div ref="messagesContainer" class="space-y-3 mb-4 max-h-96 overflow-y-auto">
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="max-w-[80%] p-3 rounded-lg text-sm"
                        :class="msg.sender.id === page.props.auth.user.id
                            ? 'ml-auto bg-teal-600 text-white'
                            : 'bg-gray-100 text-gray-700'"
                    >
                        {{ msg.message }}
                    </div>
                    <p v-if="messages.length === 0" class="text-xs text-gray-400 text-center py-6">
                        Belum ada pesan. Mulai percakapan di bawah.
                    </p>
                </div>

                <p v-if="sendError" class="text-xs text-red-600 mb-2" role="alert">{{ sendError }}</p>

                <p v-if="otherPartyTyping" class="text-xs text-gray-400 italic mb-2">
                    {{ consultation.psychologist_profile.user.name }} sedang menulis…
                </p>

                <!-- Samakan persis dengan backend (sendMessage() cuma izinkan
                     status 'scheduled'), bukan sekadar "bukan completed/cancelled". -->
                <div v-if="consultation.status === 'scheduled'" class="flex gap-2">
                    <input
                        v-model="newMessage"
                        @input="handleTyping"
                        @keyup.enter="sendMessage"
                        type="text"
                        placeholder="Tulis pesan…"
                        class="flex-1 rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    />
                    <button
                        @click="sendMessage"
                        :disabled="sending || !newMessage.trim()"
                        class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                    >
                        {{ sending ? 'Mengirim…' : 'Kirim' }}
                    </button>
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
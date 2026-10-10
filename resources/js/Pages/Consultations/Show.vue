<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { router, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReviewForm from '@/Components/ReviewForm.vue';

const page = usePage();

const props = defineProps({
    consultation: { type: Object, required: true },
    reviewEditable: { type: Boolean, default: false },
    closesAt: { type: String, default: null },
});


const MAX_MESSAGE_LENGTH = 2000;

const messages = ref([...props.consultation.messages]);
const newMessage = ref('');
const sending = ref(false);
const sendError = ref(null);
const messagesContainer = ref(null);
const otherPartyTyping = ref(false);

const myId = page.props.auth.user.id;
let markingRead = false;
let markAgain = false;

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

/**
 * Token CSRF untuk request fetch. Cookie XSRF-TOKEN diprioritaskan karena diperbarui di setiap
 * response (sama seperti cara Inertia/axios bekerja); meta tag dipakai sebagai cadangan.
 * Token di meta tag bisa basi setelah navigasi tanpa muat ulang halaman penuh (mis. logout lalu
 * login lagi), dan request fetch akan ditolak 419.
 */
function csrfHeaders() {
    const cookie = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    if (cookie) {
        return { 'X-XSRF-TOKEN': decodeURIComponent(cookie[1]) };
    }

    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return meta ? { 'X-CSRF-TOKEN': meta } : {};
}

const socketId = () => window.Echo?.socketId?.() ?? '';

function describeError(status, data) {
    if (status === 401) return 'Sesi login sudah berakhir. Silakan login ulang.';
    if (status === 419) return 'Sesi sudah tidak valid. Muat ulang halaman lalu coba lagi.';
    if (status === 429) return 'Terlalu banyak pesan dalam waktu singkat. Tunggu sebentar lalu coba lagi.';
    if (status >= 500) return 'Terjadi kesalahan di server. Coba lagi nanti.';

    return data?.errors?.message?.[0] ?? data?.message ?? 'Pesan gagal terkirim. Coba lagi.';
}

function scrollToBottom() {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
}

function pushIfNew(message) {
    if (!messages.value.some((m) => m.id === message.id)) {
        messages.value.push(message);
        scrollToBottom();
    }
    otherPartyTyping.value = false;
    clearTimeout(typingHideTimeout);
}

function refreshClosesAt() {
    if (props.closesAt) {
        router.reload({ only: ['closesAt'], preserveScroll: true });
    }
}

const isMine = (msg) => msg.sender.id === myId;

function formatTime(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

const hasUnreadIncoming = () =>
    messages.value.some((m) => !isMine(m) && !m.read_at);

async function markIncomingAsRead() {
    if (!['scheduled', 'completed'].includes(props.consultation.status)) return;
    if (document.visibilityState !== 'visible' || !hasUnreadIncoming()) return;

    if (markingRead) {
        markAgain = true;
        return;
    }

    markingRead = true;
    try {
        const res = await fetch(route('consultations.read', props.consultation.id), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Socket-ID': socketId(),
                ...csrfHeaders(),
            },
        });

        if (res.ok) {
            const now = new Date().toISOString();
            messages.value.forEach((m) => {
                if (!isMine(m) && !m.read_at) m.read_at = now;
            });
            window.dispatchEvent(new CustomEvent('notifications:refresh'));
        }
    } catch (e) {

    } finally {
        markingRead = false;
        if (markAgain) {
            markAgain = false;
            markIncomingAsRead();
        }
    }
}

function onVisibilityChange() {
    if (document.visibilityState === 'visible') markIncomingAsRead();
}

function handleTyping() {
    if (!echoChannel || props.consultation.status !== 'scheduled') return;

    const now = Date.now();
    if (now - lastWhisperAt < 2000) return;
    lastWhisperAt = now;

    echoChannel.whisper('typing', {});
}

async function sendMessage() {

    if (sending.value) return;

    const text = newMessage.value.trim();
    if (!text) return;

    sending.value = true;
    sendError.value = null;

    try {
        const res = await fetch(route('consultations.messages.send', props.consultation.id), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Socket-ID': socketId(),
                ...csrfHeaders(),
            },
            body: JSON.stringify({ message: text }),
        });

        if (res.ok) {
            const data = await res.json();
            pushIfNew(data.data);
            newMessage.value = '';
            refreshClosesAt();
            return;
        }

        const errorData = await res.json().catch(() => null);
        sendError.value = describeError(res.status, errorData);
    } catch (e) {
        sendError.value = 'Terjadi kesalahan jaringan. Coba lagi.';
    } finally {
        sending.value = false;
    }
}

const channelName = `consultation.${props.consultation.id}`;

onMounted(() => {
    scrollToBottom();

    if (window.Echo) {
        echoChannel = window.Echo.private(channelName);

        echoChannel
            .listen('.status.changed', () => {
                router.reload({ only: ['consultation', 'reviewEditable', 'closesAt'], preserveScroll: true });
            })
            .listen('.message.sent', (e) => {
                pushIfNew(e);
                refreshClosesAt();
                if (e.sender.id !== myId) markIncomingAsRead();
            })
            .listen('.messages.read', (e) => {
                if (e.reader_id === myId) return;

                messages.value.forEach((m) => {
                    if (isMine(m) && !m.read_at) m.read_at = e.read_at;
                });
            })
            .listenForWhisper('typing', () => {
                otherPartyTyping.value = true;
                clearTimeout(typingHideTimeout);
                typingHideTimeout = setTimeout(() => {
                    otherPartyTyping.value = false;
                }, 3000);
            });
    }

    document.addEventListener('visibilitychange', onVisibilityChange);
    markIncomingAsRead();
});

onBeforeUnmount(() => {
    clearTimeout(typingHideTimeout);
    document.removeEventListener('visibilitychange', onVisibilityChange);
    window.Echo?.leave(channelName);
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

            <div v-if="$page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ $page.props.flash.success }}
            </div>

            <div
                v-if="closesAt && consultation.status === 'scheduled'"
                class="mb-4 p-3 rounded-md bg-amber-50 border border-amber-200 text-sm text-amber-800"
                role="status"
            >
                Belum ada aktivitas beberapa hari terakhir. Konsultasi ini akan ditutup otomatis pada
                <strong>{{ formatDate(closesAt) }}</strong>. Kirim pesan untuk melanjutkannya.
            </div>

            <div
                v-if="consultation.type === 'tatap_muka' && consultation.status === 'scheduled'"
                class="mb-4 p-3 rounded-md bg-teal-50 text-sm text-teal-700"
            >
                📍 Pertemuan tatap muka langsung. Detail lokasi & waktu bisa dilihat di
                catatan psikolog atau tanyakan lewat chat.
            </div>

            <div v-if="consultation.notes" class="mb-4 p-3 rounded-md bg-gray-50 text-sm text-gray-600 whitespace-pre-line">
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
                        :class="isMine(msg)
                            ? 'ml-auto bg-teal-600 text-white'
                            : 'bg-gray-100 text-gray-700'"
                    >
                        <p class="whitespace-pre-line break-words">{{ msg.message }}</p>
                        <div
                            class="mt-1 flex items-center justify-end gap-1 text-[10px]"
                            :class="isMine(msg) ? 'text-teal-100' : 'text-gray-400'"
                        >
                            <span>{{ formatTime(msg.sent_at) }}</span>
                            <span
                                v-if="isMine(msg)"
                                :class="msg.read_at ? 'text-sky-300 font-bold' : ''"
                                :title="msg.read_at ? 'Sudah dibaca' : 'Terkirim'"
                                :aria-label="msg.read_at ? 'Sudah dibaca' : 'Terkirim'"
                            >{{ msg.read_at ? '✓✓' : '✓' }}</span>
                        </div>
                    </div>
                    <p v-if="messages.length === 0" class="text-xs text-gray-400 text-center py-6">
                        Belum ada pesan. Mulai percakapan di bawah.
                    </p>
                </div>

                <p v-if="sendError" class="text-xs text-red-600 mb-2" role="alert">{{ sendError }}</p>

                <p v-if="otherPartyTyping" class="text-xs text-gray-400 italic mb-2">
                    {{ consultation.psychologist_profile.user.name }} sedang menulis…
                </p>

                <div v-if="consultation.status === 'scheduled'" class="flex gap-2">
                    <input
                        v-model="newMessage"
                        @input="handleTyping"
                        @keyup.enter="sendMessage"
                        type="text"
                        :maxlength="MAX_MESSAGE_LENGTH"
                        aria-label="Tulis pesan"
                        placeholder="Tulis pesan…"
                        class="flex-1 rounded-md border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                    />
                    <button
                        type="button"
                        @click="sendMessage"
                        :disabled="sending || !newMessage.trim()"
                        class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40 hover:bg-teal-700"
                    >
                        {{ sending ? 'Mengirim…' : 'Kirim' }}
                    </button>
                </div>

                <div v-if="consultation.status === 'completed'" class="mt-6">
                    <ReviewForm
                        :consultation-id="consultation.id"
                        :review="consultation.review"
                        :editable="reviewEditable"
                    />
                </div>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

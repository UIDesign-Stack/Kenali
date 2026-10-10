<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { router, Link, Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReviewReplyForm from '@/Components/ReviewReplyForm.vue';

const page = usePage();

const props = defineProps({
    consultation: { type: Object, required: true },

    topAlternative: { type: String, default: null },
    replyEditable: { type: Boolean, default: false },
    closesAt: { type: String, default: null },
});


const MAX_MESSAGE_LENGTH = 2000;
const MAX_NOTES_LENGTH = 2000;
const MAX_REASON_LENGTH = 1000;

const messages = ref([...props.consultation.messages]);
const newMessage = ref('');
const sending = ref(false);
const sendError = ref(null);
const messagesContainer = ref(null);
const actionError = ref(null);
const statusProcessing = ref(false);
const otherPartyTyping = ref(false);
const myId = page.props.auth.user.id;

let markingRead = false;
let markAgain = false;
let echoChannel = null;
let typingHideTimeout = null;
let lastWhisperAt = 0;

const typeLabel = {
    chat: 'Chat',
    tatap_muka: 'Tatap Muka Langsung',
};

const locationNote = ref('');

const scheduleForm = useForm({
    status: 'scheduled',
    scheduled_at: '',
});


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

function formatDateTime(dateStr) {
    return new Date(dateStr).toLocaleString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
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
    if (props.consultation.status !== 'scheduled') return;
    if (document.visibilityState !== 'visible' || !hasUnreadIncoming()) return;

    if (markingRead) {
        markAgain = true;
        return;
    }

    markingRead = true;
    try {
        const res = await fetch(route('psikolog.consultations.read', props.consultation.id), {
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
        const res = await fetch(route('psikolog.consultations.messages.send', props.consultation.id), {
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
                router.reload({ only: ['consultation', 'topAlternative', 'replyEditable', 'closesAt'], preserveScroll: true });
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

function acceptAndSchedule() {
    if (scheduleForm.processing) return;

    actionError.value = null;
    scheduleForm.status = 'scheduled';

    const extra = {};
    const note = locationNote.value.trim();

    if (note) {

        const combined = [props.consultation.notes, `[Psikolog] ${note}`].filter(Boolean).join('\n');

        if (combined.length > MAX_NOTES_LENGTH) {
            actionError.value = 'Catatan lokasi terlalu panjang. Persingkat catatan lokasi Anda.';
            return;
        }

        extra.notes = combined;
    }

    scheduleForm.transform((data) => ({ ...data, ...extra })).patch(
        route('psikolog.consultations.update-status', props.consultation.id),
        {
            preserveScroll: true,
            onError: (errors) => {
                actionError.value = errors.scheduled_at ?? errors.notes ?? errors.status ?? 'Gagal menjadwalkan konsultasi.';
            },
        }
    );
}

function markCompleted() {
    if (statusProcessing.value) return;
    if (!confirm('Tandai konsultasi ini selesai? Setelah selesai, status tidak bisa diubah lagi dan chat ditutup.')) return;

    actionError.value = null;
    statusProcessing.value = true;

    router.patch(route('psikolog.consultations.update-status', props.consultation.id), {
        status: 'completed',
    }, {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = errors.status ?? 'Gagal menandai selesai.';
        },
        onFinish: () => (statusProcessing.value = false),
    });
}

function cancelConsultation() {
    if (statusProcessing.value) return;

    const reason = prompt('Alasan pembatalan (akan terlihat oleh pasien):');
    const trimmed = reason?.trim();
    if (!trimmed) return;

    if (trimmed.length > MAX_REASON_LENGTH) {
        alert(`Alasan terlalu panjang (${trimmed.length} karakter). Maksimal ${MAX_REASON_LENGTH} karakter.`);
        return;
    }

    actionError.value = null;
    statusProcessing.value = true;

    router.patch(route('psikolog.consultations.update-status', props.consultation.id), {
        status: 'cancelled',
        cancelled_reason: trimmed,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = errors.cancelled_reason ?? errors.status ?? 'Gagal membatalkan konsultasi.';
        },
        onFinish: () => (statusProcessing.value = false),
    });
}
</script>

<template>
    <Head :title="consultation.user.name" />

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
            <p class="text-xs text-gray-400 mb-3">Jenis: {{ typeLabel[consultation.type] ?? consultation.type }}</p>

            <p v-if="consultation.scheduled_at && consultation.status === 'scheduled'" class="text-sm text-gray-700 mb-3">
                Jadwal: <strong>{{ formatDateTime(consultation.scheduled_at) }}</strong>
            </p>

            <div v-if="$page.props.flash?.success" class="mb-4 p-3 rounded-md bg-teal-50 text-teal-700 text-sm">
                {{ $page.props.flash.success }}
            </div>

            <div v-if="actionError" class="mb-4 p-3 rounded-md bg-red-50 text-red-700 text-sm" role="alert">
                {{ actionError }}
            </div>

            <div v-if="topAlternative" class="mb-4 p-3 rounded-md bg-teal-50 text-sm text-teal-700">
                Rekomendasi dari hasil tes: <strong>{{ topAlternative }}</strong>
            </div>

            <div v-if="consultation.notes" class="mb-4 p-3 rounded-md bg-gray-50 text-sm text-gray-600 whitespace-pre-line">
                "{{ consultation.notes }}"
            </div>

            <div v-if="consultation.status === 'pending'" class="p-4 border border-amber-200 bg-amber-50 rounded-lg mb-4">
                <p class="text-sm text-amber-700 mb-3">Terima permintaan ini?</p>

                <div v-if="consultation.type === 'tatap_muka'" class="mb-3">
                    <label for="location-note" class="block text-xs font-medium text-gray-600 mb-1">
                        Catatan lokasi pertemuan (opsional)
                    </label>
                    <textarea
                        id="location-note"
                        v-model="locationNote"
                        rows="2"
                        maxlength="500"
                        placeholder="Contoh: Ketemu di Klinik Kenali, Jl. Contoh No. 1"
                        class="w-full rounded-md border-gray-300 text-sm"
                    ></textarea>
                </div>

                <label for="scheduled-at" class="block text-xs font-medium text-gray-600 mb-1">Jadwal</label>
                <input
                    id="scheduled-at"
                    v-model="scheduleForm.scheduled_at"
                    type="datetime-local"
                    class="w-full mb-3 rounded-md border-gray-300 text-sm"
                />
                <p v-if="scheduleForm.errors.scheduled_at" class="text-xs text-red-600 mb-2" role="alert">
                    {{ scheduleForm.errors.scheduled_at }}
                </p>
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="acceptAndSchedule"
                        :disabled="scheduleForm.processing || statusProcessing || !scheduleForm.scheduled_at"
                        class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40"
                    >
                        {{ scheduleForm.processing ? 'Memproses…' : 'Terima & Jadwalkan' }}
                    </button>
                    <button
                        type="button"
                        @click="cancelConsultation"
                        :disabled="scheduleForm.processing || statusProcessing"
                        class="px-4 py-2 rounded-md bg-gray-100 text-gray-600 text-sm font-medium disabled:opacity-40"
                    >
                        Tolak
                    </button>
                </div>
            </div>

            <template v-if="consultation.status === 'scheduled'">
                <div
                    v-if="closesAt"
                    class="mb-4 p-3 rounded-md bg-amber-50 border border-amber-200 text-sm text-amber-800"
                    role="status"
                >
                    Belum ada aktivitas beberapa hari terakhir. Konsultasi ini akan ditutup otomatis pada
                    <strong>{{ formatDate(closesAt) }}</strong>. Kirim pesan jika ingin melanjutkannya.
                </div>

                <div v-if="consultation.type === 'tatap_muka'" class="mb-4 p-3 rounded-md bg-teal-50 text-sm text-teal-700">
                    📍 Pertemuan tatap muka langsung terjadwal. Pastikan detail lokasi
                    sudah disampaikan ke user lewat chat di bawah.
                </div>

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
                </div>

                <p v-if="sendError" class="text-xs text-red-600 mb-2" role="alert">{{ sendError }}</p>

                <p v-if="otherPartyTyping" class="text-xs text-gray-400 italic mb-2">
                    {{ consultation.user.name }} sedang menulis…
                </p>

                <div class="flex gap-2 mb-4">
                    <input
                        v-model="newMessage"
                        @input="handleTyping"
                        @keyup.enter="sendMessage"
                        type="text"
                        :maxlength="MAX_MESSAGE_LENGTH"
                        aria-label="Tulis pesan"
                        placeholder="Tulis pesan…"
                        class="flex-1 rounded-md border-gray-300 text-sm"
                    />
                    <button
                        type="button"
                        @click="sendMessage"
                        :disabled="sending || !newMessage.trim()"
                        class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm disabled:opacity-40"
                    >
                        {{ sending ? 'Mengirim…' : 'Kirim' }}
                    </button>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        type="button"
                        @click="markCompleted"
                        :disabled="statusProcessing"
                        class="text-xs text-gray-500 hover:text-green-600 disabled:opacity-40"
                    >
                        Tandai selesai
                    </button>
                    <button
                        type="button"
                        @click="cancelConsultation"
                        :disabled="statusProcessing"
                        class="text-xs text-gray-500 hover:text-red-600 disabled:opacity-40"
                    >
                        Batalkan konsultasi
                    </button>
                </div>
            </template>

            <div v-if="consultation.status === 'completed'">
                <div class="text-center text-sm text-green-600 py-6">
                    Konsultasi ini sudah selesai.
                </div>

                <ReviewReplyForm
                    v-if="consultation.review"
                    :review="consultation.review"
                    :editable="replyEditable"
                />
                <p v-else class="text-center text-xs text-gray-400">Pasien belum memberi ulasan.</p>
            </div>
            <div v-if="consultation.status === 'cancelled'" class="text-center text-sm text-gray-500 py-6">
                Dibatalkan: {{ consultation.cancelled_reason }}
            </div>
        </div>
    </AuthenticatedLayout>
</template>

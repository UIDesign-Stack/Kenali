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

// ---------------------------------------------------------------
// UI only: avatar & lencana status
// ---------------------------------------------------------------
const statusText = {
    pending: 'Menunggu respon',
    scheduled: 'Terjadwal',
    completed: 'Selesai',
    cancelled: 'Dibatalkan',
};

const statusChip = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    scheduled: 'bg-sky-50 text-sky-700 ring-sky-600/20',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    cancelled: 'bg-gray-100 text-gray-500 ring-gray-500/20',
};

function initials(name) {
    if (!name) return '?';
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() ?? '')
        .join('');
}

const fieldClass =
    'w-full rounded-xl border-gray-200 bg-white text-sm placeholder:text-gray-400 focus:border-indigo-400 focus:ring-indigo-400';
</script>

<template>
    <Head :title="consultation.user.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('psikolog.consultations.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← Konsultasi
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">{{ consultation.user.name }}</h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-4">

            <!-- Notifikasi -->
            <div
                v-if="$page.props.flash?.success"
                class="flex items-center gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200"
                role="status"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                </span>
                {{ $page.props.flash.success }}
            </div>

            <div
                v-if="actionError"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                role="alert"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                <span>{{ actionError }}</span>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

                <!-- ===== Kiri: area utama sesuai status ===== -->
                <section class="overflow-hidden rounded-3xl bg-white ring-1 ring-gray-200/80 shadow-sm lg:col-span-3">

                    <!-- Kepala -->
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-400 to-indigo-500 text-sm font-bold text-white">
                                {{ initials(consultation.user.name) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ consultation.user.name }}</p>
                                <p class="text-xs text-gray-400">Jenis: {{ typeLabel[consultation.type] ?? consultation.type }}</p>
                            </div>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="statusChip[consultation.status] ?? 'bg-gray-100 text-gray-500 ring-gray-500/20'"
                        >
                            {{ statusText[consultation.status] ?? consultation.status }}
                        </span>
                    </div>

                    <!-- Pending: terima / tolak -->
                    <div v-if="consultation.status === 'pending'" class="p-5 sm:p-6">
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                            <p class="text-sm font-semibold text-amber-900">Terima permintaan ini?</p>

                            <div v-if="consultation.type === 'tatap_muka'" class="mt-4">
                                <label for="location-note" class="mb-1.5 block text-xs font-medium text-gray-600">
                                    Catatan lokasi pertemuan (opsional)
                                </label>
                                <textarea
                                    id="location-note"
                                    v-model="locationNote"
                                    rows="2"
                                    maxlength="500"
                                    placeholder="Contoh: Ketemu di Klinik Kenali, Jl. Contoh No. 1"
                                    :class="[fieldClass, 'resize-none']"
                                ></textarea>
                            </div>

                            <label for="scheduled-at" class="mb-1.5 mt-4 block text-xs font-medium text-gray-600">Jadwal</label>
                            <input
                                id="scheduled-at"
                                v-model="scheduleForm.scheduled_at"
                                type="datetime-local"
                                :class="fieldClass"
                            />
                            <p v-if="scheduleForm.errors.scheduled_at" class="mt-1.5 text-xs text-rose-600" role="alert">
                                {{ scheduleForm.errors.scheduled_at }}
                            </p>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="acceptAndSchedule"
                                    :disabled="scheduleForm.processing || statusProcessing || !scheduleForm.scheduled_at"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                                >
                                    {{ scheduleForm.processing ? 'Memproses…' : 'Terima & Jadwalkan' }}
                                </button>
                                <button
                                    type="button"
                                    @click="cancelConsultation"
                                    :disabled="scheduleForm.processing || statusProcessing"
                                    class="inline-flex items-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-rose-600 ring-1 ring-rose-200 transition hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Tolak
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Scheduled: percakapan -->
                    <template v-else-if="consultation.status === 'scheduled'">
                        <div
                            ref="messagesContainer"
                            class="h-[26rem] space-y-3 overflow-y-auto bg-gradient-to-b from-slate-50 to-white p-5"
                        >
                            <div
                                v-for="msg in messages"
                                :key="msg.id"
                                class="max-w-[80%] px-4 py-2.5 text-sm shadow-sm"
                                :class="isMine(msg)
                                    ? 'ml-auto rounded-2xl rounded-br-md bg-gradient-to-br from-teal-600 to-sky-600 text-white'
                                    : 'rounded-2xl rounded-bl-md bg-white text-gray-700 ring-1 ring-gray-200'"
                            >
                                <p class="whitespace-pre-line break-words">{{ msg.message }}</p>
                                <div
                                    class="mt-1 flex items-center justify-end gap-1 text-[10px]"
                                    :class="isMine(msg) ? 'text-teal-100' : 'text-gray-400'"
                                >
                                    <span>{{ formatTime(msg.sent_at) }}</span>
                                    <span
                                        v-if="isMine(msg)"
                                        :class="msg.read_at ? 'font-bold text-amber-200' : ''"
                                        :title="msg.read_at ? 'Sudah dibaca' : 'Terkirim'"
                                        :aria-label="msg.read_at ? 'Sudah dibaca' : 'Terkirim'"
                                    >{{ msg.read_at ? '✓✓' : '✓' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 p-4">
                            <p v-if="sendError" class="mb-2 rounded-xl bg-rose-50 px-3 py-2 text-xs text-rose-700" role="alert">{{ sendError }}</p>

                            <p v-if="otherPartyTyping" class="mb-2 text-xs italic text-gray-400">
                                {{ consultation.user.name }} sedang menulis…
                            </p>

                            <div class="flex gap-2">
                                <input
                                    v-model="newMessage"
                                    @input="handleTyping"
                                    @keyup.enter="sendMessage"
                                    type="text"
                                    :maxlength="MAX_MESSAGE_LENGTH"
                                    aria-label="Tulis pesan"
                                    placeholder="Tulis pesan…"
                                    class="flex-1 rounded-full border-gray-200 bg-gray-50 px-5 text-sm placeholder:text-gray-400 focus:border-indigo-400 focus:bg-white focus:ring-indigo-400"
                                />
                                <button
                                    type="button"
                                    @click="sendMessage"
                                    :disabled="sending || !newMessage.trim()"
                                    class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-500/30 transition hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                                >
                                    {{ sending ? 'Mengirim…' : 'Kirim' }}
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Completed -->
                    <div v-else-if="consultation.status === 'completed'" class="p-5 sm:p-6">
                        <div class="mb-5 flex items-center justify-center gap-2 rounded-2xl bg-emerald-50 py-5 text-sm font-medium text-emerald-700 ring-1 ring-emerald-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            Konsultasi ini sudah selesai.
                        </div>

                        <ReviewReplyForm
                            v-if="consultation.review"
                            :review="consultation.review"
                            :editable="replyEditable"
                        />
                        <p v-else class="rounded-2xl bg-gray-50 py-5 text-center text-xs text-gray-400">Pasien belum memberi ulasan.</p>
                    </div>

                    <!-- Cancelled -->
                    <div v-else-if="consultation.status === 'cancelled'" class="p-5 sm:p-6">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 text-center text-sm text-gray-500">
                            Dibatalkan: {{ consultation.cancelled_reason }}
                        </div>
                    </div>
                </section>

                <!-- ===== Kanan: informasi & aksi ===== -->
                <aside class="space-y-4 lg:col-span-2">

                    <div
                        v-if="consultation.scheduled_at && consultation.status === 'scheduled'"
                        class="rounded-2xl bg-sky-50 p-5 ring-1 ring-sky-100"
                    >
                        <p class="text-xs font-medium text-sky-600">Jadwal</p>
                        <p class="mt-1 text-sm text-sky-950">
                            Jadwal: <strong>{{ formatDateTime(consultation.scheduled_at) }}</strong>
                        </p>
                    </div>

                    <div v-if="topAlternative" class="rounded-2xl bg-gradient-to-br from-indigo-50 to-violet-50 p-5 ring-1 ring-indigo-100">
                        <p class="text-xs font-medium text-indigo-500">Rekomendasi dari hasil tes</p>
                        <p class="mt-1 text-base font-bold text-indigo-900">{{ topAlternative }}</p>
                    </div>

                    <div v-if="consultation.notes" class="rounded-2xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm">
                        <p class="mb-2 text-xs font-semibold text-gray-400">Catatan</p>
                        <p class="whitespace-pre-line border-l-4 border-indigo-200 pl-3 text-sm italic leading-relaxed text-gray-600">
                            "{{ consultation.notes }}"
                        </p>
                    </div>

                    <template v-if="consultation.status === 'scheduled'">
                        <div
                            v-if="closesAt"
                            class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                            role="status"
                        >
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            <p>
                                Belum ada aktivitas beberapa hari terakhir. Konsultasi ini akan ditutup otomatis pada
                                <strong>{{ formatDate(closesAt) }}</strong>. Kirim pesan jika ingin melanjutkannya.
                            </p>
                        </div>

                        <div
                            v-if="consultation.type === 'tatap_muka'"
                            class="flex items-start gap-3 rounded-2xl bg-teal-50 p-4 text-sm text-teal-800 ring-1 ring-teal-100"
                        >
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                            <p>
                                Pertemuan tatap muka langsung terjadwal. Pastikan detail lokasi
                                sudah disampaikan ke user lewat chat di samping.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm">
                            <p class="mb-3 text-xs font-semibold text-gray-400">Aksi konsultasi</p>
                            <div class="flex flex-col gap-2">
                                <button
                                    type="button"
                                    @click="markCompleted"
                                    :disabled="statusProcessing"
                                    class="inline-flex items-center justify-center rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Tandai selesai
                                </button>
                                <button
                                    type="button"
                                    @click="cancelConsultation"
                                    :disabled="statusProcessing"
                                    class="inline-flex items-center justify-center rounded-xl bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-600 ring-1 ring-rose-200 transition hover:bg-rose-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Batalkan konsultasi
                                </button>
                            </div>
                        </div>
                    </template>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

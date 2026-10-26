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
</script>

<template>
    <Head :title="consultation.psychologist_profile.user.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <Link
                    :href="route('consultations.index')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-medium text-gray-500 ring-1 ring-gray-200 transition hover:bg-indigo-50 hover:text-indigo-600 hover:ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    ← Konsultasi
                </Link>
                <h1 class="text-xl font-semibold text-gray-800">
                    {{ consultation.psychologist_profile.user.name }}
                </h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

                <!-- ===== Kiri: percakapan ===== -->
                <section class="overflow-hidden rounded-3xl bg-white ring-1 ring-gray-200/80 shadow-sm lg:col-span-3">

                    <!-- Kepala percakapan -->
                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-400 to-indigo-500 text-sm font-bold text-white">
                                {{ initials(consultation.psychologist_profile.user.name) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ consultation.psychologist_profile.user.name }}</p>
                                <p class="text-xs text-gray-400">{{ typeLabel[consultation.type] ?? consultation.type }}</p>
                            </div>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="statusChip[consultation.status] ?? 'bg-gray-100 text-gray-500 ring-gray-500/20'"
                        >
                            {{ statusLabel[consultation.status] ?? consultation.status }}
                        </span>
                    </div>

                    <!-- Menunggu -->
                    <div
                        v-if="consultation.status === 'pending'"
                        class="m-5 rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center text-sm text-amber-800"
                    >
                        <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-400 text-amber-950">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        </span>
                        Menunggu psikolog menerima permintaan konsultasimu.
                    </div>

                    <!-- Dibatalkan -->
                    <div
                        v-else-if="consultation.status === 'cancelled'"
                        class="m-5 rounded-2xl border border-gray-200 bg-gray-50 p-8 text-center"
                    >
                        <p class="mb-2 text-sm text-gray-600">Konsultasi ini dibatalkan.</p>
                        <p v-if="consultation.cancelled_reason" class="text-sm italic text-gray-500">
                            "{{ consultation.cancelled_reason }}"
                        </p>
                    </div>

                    <!-- Percakapan -->
                    <template v-else>
                        <div
                            ref="messagesContainer"
                            class="h-[26rem] space-y-3 overflow-y-auto bg-gradient-to-b from-slate-50 to-white p-5"
                        >
                            <div
                                v-for="msg in messages"
                                :key="msg.id"
                                class="max-w-[80%] px-4 py-2.5 text-sm shadow-sm"
                                :class="isMine(msg)
                                    ? 'ml-auto rounded-2xl rounded-br-md bg-gradient-to-br from-indigo-600 to-violet-600 text-white'
                                    : 'rounded-2xl rounded-bl-md bg-white text-gray-700 ring-1 ring-gray-200'"
                            >
                                <p class="whitespace-pre-line break-words">{{ msg.message }}</p>
                                <div
                                    class="mt-1 flex items-center justify-end gap-1 text-[10px]"
                                    :class="isMine(msg) ? 'text-indigo-100' : 'text-gray-400'"
                                >
                                    <span>{{ formatTime(msg.sent_at) }}</span>
                                    <span
                                        v-if="isMine(msg)"
                                        :class="msg.read_at ? 'font-bold text-teal-200' : ''"
                                        :title="msg.read_at ? 'Sudah dibaca' : 'Terkirim'"
                                        :aria-label="msg.read_at ? 'Sudah dibaca' : 'Terkirim'"
                                    >{{ msg.read_at ? '✓✓' : '✓' }}</span>
                                </div>
                            </div>
                            <p v-if="messages.length === 0" class="py-10 text-center text-xs text-gray-400">
                                Belum ada pesan. Mulai percakapan di bawah.
                            </p>
                        </div>

                        <div class="border-t border-gray-100 p-4">
                            <p v-if="sendError" class="mb-2 rounded-xl bg-rose-50 px-3 py-2 text-xs text-rose-700" role="alert">{{ sendError }}</p>

                            <p v-if="otherPartyTyping" class="mb-2 text-xs italic text-gray-400">
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

                            <p
                                v-else
                                class="rounded-xl bg-gray-50 px-4 py-3 text-center text-xs text-gray-400"
                            >
                                Percakapan ini sudah ditutup.
                            </p>
                        </div>
                    </template>
                </section>

                <!-- ===== Kanan: info & ulasan ===== -->
                <aside class="space-y-4 lg:col-span-2">
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
                        v-if="closesAt && consultation.status === 'scheduled'"
                        class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                        role="status"
                    >
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                        <p>
                            Belum ada aktivitas beberapa hari terakhir. Konsultasi ini akan ditutup otomatis pada
                            <strong>{{ formatDate(closesAt) }}</strong>. Kirim pesan untuk melanjutkannya.
                        </p>
                    </div>

                    <div
                        v-if="consultation.type === 'tatap_muka' && consultation.status === 'scheduled'"
                        class="flex items-start gap-3 rounded-2xl bg-teal-50 p-4 text-sm text-teal-800 ring-1 ring-teal-100"
                    >
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                        <p>
                            Pertemuan tatap muka langsung. Detail lokasi & waktu bisa dilihat di
                            catatan psikolog atau tanyakan lewat chat.
                        </p>
                    </div>

                    <div v-if="consultation.notes" class="rounded-2xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm">
                        <p class="mb-2 text-xs font-semibold text-gray-400">Catatanmu</p>
                        <p class="whitespace-pre-line border-l-4 border-indigo-200 pl-3 text-sm italic leading-relaxed text-gray-600">
                            "{{ consultation.notes }}"
                        </p>
                    </div>

                    <div
                        v-if="consultation.status === 'completed'"
                        class="rounded-3xl bg-white p-5 ring-1 ring-gray-200/80 shadow-sm sm:p-6"
                    >
                        <ReviewForm
                            :consultation-id="consultation.id"
                            :review="consultation.review"
                            :editable="reviewEditable"
                        />
                    </div>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

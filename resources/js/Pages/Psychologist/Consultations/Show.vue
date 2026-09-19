<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { router, Link, Head, useForm, usePage } from '@inertiajs/vue3';
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
const actionError = ref(null);

const typeLabel = {
    chat: 'Chat',
    tatap_muka: 'Tatap Muka Langsung',
};

// Field terpisah untuk catatan lokasi yang diketik psikolog -- TIDAK
// langsung dikirim sebagai `notes` mentah, supaya tidak menimpa catatan
// asli klien (lihat acceptAndSchedule()).
const locationNote = ref('');

const scheduleForm = useForm({
    status: 'scheduled',
    scheduled_at: '',
});

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
    if (!messages.value.some((m) => m.id === message.id)) {
        messages.value.push(message);
        scrollToBottom();
    }
}

async function sendMessage() {
    if (!newMessage.value.trim()) return;

    sending.value = true;
    sendError.value = null;

    try {
        const res = await fetch(route('psikolog.consultations.messages.send', props.consultation.id), {
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

    window.Echo.private(channelName).listen('.message.sent', (e) => {
        pushIfNew(e);
    });
});

onBeforeUnmount(() => {
    window.Echo.leave(channelName);
});

function acceptAndSchedule() {
    actionError.value = null;
    scheduleForm.status = 'scheduled';

    // Cuma sertakan `notes` di payload KALAU psikolog benar-benar mengisi
    // catatan lokasi, dan gabungkan (bukan timpa) dengan catatan asli
    // klien -- sebelumnya field notes selalu terkirim (walau kosong),
    // menimpa/menghapus catatan asli klien setiap kali konsultasi diterima.
    const extra = {};
    if (locationNote.value.trim()) {
        extra.notes = [props.consultation.notes, `[Psikolog] ${locationNote.value.trim()}`]
            .filter(Boolean)
            .join('\n');
    }

    scheduleForm.transform((data) => ({ ...data, ...extra })).patch(
        route('psikolog.consultations.update-status', props.consultation.id),
        {
            preserveScroll: true,
            onError: (errors) => {
                actionError.value = errors.scheduled_at ?? errors.status ?? 'Gagal menjadwalkan konsultasi.';
            },
        }
    );
}

function markCompleted() {
    actionError.value = null;

    router.patch(route('psikolog.consultations.update-status', props.consultation.id), {
        status: 'completed',
    }, {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = errors.status ?? 'Gagal menandai selesai.';
        },
    });
}

function cancelConsultation() {
    const reason = prompt('Alasan pembatalan:');
    if (!reason) return;

    actionError.value = null;

    router.patch(route('psikolog.consultations.update-status', props.consultation.id), {
        status: 'cancelled',
        cancelled_reason: reason,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = errors.cancelled_reason ?? errors.status ?? 'Gagal membatalkan konsultasi.';
        },
    });
}

function topAlternative() {
    return props.consultation.test_session?.result?.details?.[0]?.alternative?.name ?? null;
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

            <div v-if="actionError" class="mb-4 p-3 rounded-md bg-red-50 text-red-700 text-sm" role="alert">
                {{ actionError }}
            </div>

            <div v-if="topAlternative()" class="mb-4 p-3 rounded-md bg-teal-50 text-sm text-teal-700">
                Rekomendasi dari hasil tes: <strong>{{ topAlternative() }}</strong>
            </div>

            <div v-if="consultation.notes" class="mb-4 p-3 rounded-md bg-gray-50 text-sm text-gray-600 whitespace-pre-line">
                "{{ consultation.notes }}"
            </div>

            <div v-if="consultation.status === 'pending'" class="p-4 border border-amber-200 bg-amber-50 rounded-lg mb-4">
                <p class="text-sm text-amber-700 mb-3">Terima permintaan ini?</p>

                <div v-if="consultation.type === 'tatap_muka'" class="mb-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Catatan lokasi pertemuan (opsional)
                    </label>
                    <textarea
                        v-model="locationNote"
                        rows="2"
                        placeholder="Contoh: Ketemu di Klinik Kenali, Jl. Contoh No. 1"
                        class="w-full rounded-md border-gray-300 text-sm"
                    ></textarea>
                </div>

                <input
                    v-model="scheduleForm.scheduled_at"
                    type="datetime-local"
                    class="w-full mb-3 rounded-md border-gray-300 text-sm"
                />
                <p v-if="scheduleForm.errors.scheduled_at" class="text-xs text-red-600 mb-2">
                    {{ scheduleForm.errors.scheduled_at }}
                </p>
                <div class="flex gap-2">
                    <button
                        @click="acceptAndSchedule"
                        :disabled="scheduleForm.processing || !scheduleForm.scheduled_at"
                        class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm font-medium disabled:opacity-40"
                    >
                        {{ scheduleForm.processing ? 'Memproses…' : 'Terima & Jadwalkan' }}
                    </button>
                    <button
                        @click="cancelConsultation"
                        class="px-4 py-2 rounded-md bg-gray-100 text-gray-600 text-sm font-medium"
                    >
                        Tolak
                    </button>
                </div>
            </div>

            <template v-if="consultation.status === 'scheduled'">
                <div v-if="consultation.type === 'tatap_muka'" class="mb-4 p-3 rounded-md bg-teal-50 text-sm text-teal-700">
                    📍 Pertemuan tatap muka langsung terjadwal. Pastikan detail lokasi
                    sudah disampaikan ke user lewat chat di bawah.
                </div>

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
                </div>

                <p v-if="sendError" class="text-xs text-red-600 mb-2" role="alert">{{ sendError }}</p>

                <div class="flex gap-2 mb-4">
                    <input
                        v-model="newMessage"
                        @keyup.enter="sendMessage"
                        type="text"
                        placeholder="Tulis pesan…"
                        class="flex-1 rounded-md border-gray-300 text-sm"
                    />
                    <button
                        @click="sendMessage"
                        :disabled="sending || !newMessage.trim()"
                        class="px-4 py-2 rounded-md bg-teal-600 text-white text-sm"
                    >
                        {{ sending ? 'Mengirim…' : 'Kirim' }}
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
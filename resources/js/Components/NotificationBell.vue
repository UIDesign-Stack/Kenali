<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();

const notifications = ref([]);
const unreadCount = ref(0);
const isOpen = ref(false);
const rootEl = ref(null);

async function fetchNotifications() {
    try {
        const res = await fetch(route('notifications.index'), {
            headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) return;
        const data = await res.json();
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch (e) {
        console.error('Gagal ambil notifikasi:', e);
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function openNotification(notif) {
    if (!notif.read_at) {
        try {
            const res = await fetch(route('notifications.read', notif.id), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) {
                console.error('Gagal menandai notifikasi sebagai dibaca:', res.status);
            }
        } catch (e) {
            console.error('Gagal menandai notifikasi sebagai dibaca:', e);
        }
    }

    isOpen.value = false;

    if (notif.data.url) {
        router.visit(notif.data.url);
    }
}

async function markAllRead() {
    await fetch(route('notifications.read-all'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
    fetchNotifications();
}

function toggleDropdown() {
    isOpen.value = !isOpen.value;
}

function handleClickOutside(event) {
    if (isOpen.value && rootEl.value && !rootEl.value.contains(event.target)) {
        isOpen.value = false;
    }
}

function timeAgo(dateStr) {
    const seconds = Math.floor((new Date() - new Date(dateStr)) / 1000);
    if (seconds < 60) return 'Baru saja';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' menit lalu';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' jam lalu';
    return Math.floor(seconds / 86400) + ' hari lalu';
}

let channelName = null;

onMounted(() => {

    fetchNotifications();

    document.addEventListener('click', handleClickOutside);

    const userId = page.props.auth.user.id;
    channelName = `App.Models.User.${userId}`;

    window.Echo.private(channelName).notification((payload) => {

        notifications.value.unshift({
            id: payload.id,
            data: {
                title: payload.title,
                body: payload.body,
                url: payload.url,
                consultation_id: payload.consultation_id,
            },
            read_at: null,
            created_at: new Date().toISOString(),
        });
        unreadCount.value += 1;
    });
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    if (channelName) {
        window.Echo.leave(channelName);
    }
});
</script>

<template>
    <div class="relative" ref="rootEl">
        <button
            @click="toggleDropdown"
            :aria-expanded="isOpen"
            aria-haspopup="true"
            aria-label="Notifikasi"
            class="relative p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <div
            v-if="isOpen"
            role="menu"
            class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg border border-gray-200 z-50 max-h-96 overflow-y-auto"
        >
            <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                <span class="text-sm font-semibold text-gray-700">Notifikasi</span>
                <button
                    v-if="unreadCount > 0"
                    @click="markAllRead"
                    class="text-xs text-teal-600 hover:text-teal-800"
                >
                    Tandai semua dibaca
                </button>
            </div>

            <button
                v-for="notif in notifications"
                :key="notif.id"
                role="menuitem"
                @click="openNotification(notif)"
                class="w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition"
                :class="{ 'bg-teal-50': !notif.read_at }"
            >
                <p class="text-sm font-medium text-gray-800">{{ notif.data.title }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ notif.data.body }}</p>
                <p class="text-[10px] text-gray-400 mt-1">{{ timeAgo(notif.created_at) }}</p>
            </button>

            <p v-if="notifications.length === 0" class="text-sm text-gray-400 text-center py-8">
                Belum ada notifikasi.
            </p>
        </div>
    </div>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    dashboardLabel: { type: String, default: 'Lanjut ke dashboard' },
});
</script>

<template>
    <Link v-if="$page.props.auth.user" :href="route('dashboard')">{{ dashboardLabel }}</Link>
    <Link v-else-if="canRegister" :href="route('register')">Mulai tes</Link>
    <Link v-else-if="canLogin" :href="route('login')">Masuk untuk mulai</Link>
</template>

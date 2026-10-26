<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({ canLogin: Boolean, canRegister: Boolean });
</script>

<template>
    <header class="site-header sticky top-0 z-30 backdrop-blur">
        <div class="wrap flex items-center justify-between py-3.5">
            <a href="/" class="flex items-center gap-2.5" aria-label="Kenali, ke beranda">
                <ApplicationLogo class="h-9 w-9" />
                <span class="display text-2xl font-extrabold tracking-tight">Kenali</span>
            </a>

            <nav class="hidden items-center gap-8 text-[15px] font-semibold md:flex" aria-label="Bagian halaman">
                <a href="#tiga-hal" class="nav-link">Tiga hal yang dibaca</a>
                <a href="#cara-kerja" class="nav-link">Cara kerja</a>
            </nav>

            <div class="flex items-center gap-4">
                <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-sm">Dashboard</Link>
                <template v-else>
                    <Link v-if="canLogin" :href="route('login')" class="nav-link text-[15px] font-semibold">Masuk</Link>
                    <Link v-if="canRegister" :href="route('register')" class="btn btn-sm">Daftar</Link>
                </template>
            </div>
        </div>
    </header>
</template>

<style scoped>
.site-header { background: rgb(255 255 255 / 0.8); }
</style>
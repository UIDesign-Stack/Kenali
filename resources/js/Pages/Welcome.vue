<script setup>
import { Head } from '@inertiajs/vue3';
import '../../css/kenali.css';

import { provideScrollOrbs } from '@/Composables/useScrollOrbs';
import SiteHeader from '@/Components/Landing/SiteHeader.vue';
import SiteFooter from '@/Components/Landing/SiteFooter.vue';
import BackgroundBlobs from '@/Components/Landing/BackgroundBlobs.vue';
import FlyLayer from '@/Components/Landing/FlyLayer.vue';
import HeroSection from '@/Components/Landing/HeroSection.vue';
import PillarsSection from '@/Components/Landing/PillarsSection.vue';
import HowItWorksSection from '@/Components/Landing/HowItWorksSection.vue';
import CtaSection from '@/Components/Landing/CtaSection.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});


const { fly } = provideScrollOrbs();
</script>

<template>
    <Head title="Kenali - temukan minat, bakat, dan karaktermu">
        <meta
            name="description"
            content="Kenali membantumu memahami minat, bakat, dan karakter lewat sistem pendukung keputusan, lalu memberi rekomendasi yang cocok untukmu."
        />
        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=bricolage-grotesque:600,700,800|figtree:400,500,600,700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="kenali min-h-screen antialiased">
        <SiteHeader :can-login="canLogin" :can-register="canRegister" />
        <BackgroundBlobs />
        <FlyLayer v-if="fly" />

        <main>
            <HeroSection :can-login="canLogin" :can-register="canRegister" />
            <PillarsSection />
            <HowItWorksSection />
            <CtaSection :can-login="canLogin" :can-register="canRegister" />
        </main>

        <SiteFooter :laravel-version="laravelVersion" :php-version="phpVersion" />
    </div>
</template>

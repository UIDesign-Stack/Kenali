<script setup>
import { useCareerPicker } from '@/Composables/useCareerPicker';
import AuthCta from './AuthCta.vue';
import VennChart from './VennChart.vue';
import TraitPicker from './TraitPicker.vue';
import RankingCard from './RankingCard.vue';

defineProps({ canLogin: Boolean, canRegister: Boolean });

const { picked, toggle, ranked, top, counts } = useCareerPicker();
</script>

<template>
    <section class="wrap grid gap-x-10 gap-y-12 pb-24 pt-10 md:pt-16 lg:grid-cols-12 lg:gap-y-14">
        <!-- teks utama -->
        <div class="lg:col-span-5 lg:col-start-1 lg:row-start-1 lg:self-center">
            <h1 class="display text-[2.9rem] font-extrabold leading-[1.03] tracking-tight sm:text-6xl">
                <span class="grad-text">Kenali dirimu</span> sebelum memilih jalan.
            </h1>
            <p class="muted mt-6 max-w-md text-lg leading-relaxed">
                Jawab pertanyaan tentang minat, bakat, dan karaktermu. Sistem kami menghitungnya dan mengurutkan
                pilihan yang paling cocok untukmu.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-x-7 gap-y-4">
                <AuthCta class="btn" :can-login="canLogin" :can-register="canRegister" />
                <a href="#cara-kerja" class="nav-link font-semibold">Lihat cara kerja</a>
            </div>
        </div>

        <div class="lg:col-span-7 lg:col-start-6 lg:row-start-1">
            <VennChart :counts="counts" :top="top" />
        </div>

        <div class="lg:col-span-7 lg:col-start-6 lg:row-start-2">
            <TraitPicker :picked="picked" @toggle="toggle" />
        </div>

        <div class="lg:col-span-5 lg:col-start-1 lg:row-start-2">
            <RankingCard :ranked="ranked" />
        </div>
    </section>
</template>

<script setup>
import { orbs } from '@/Data/landing';
import { useScrollOrbs } from '@/Composables/useScrollOrbs';
import AuthCta from './AuthCta.vue';

defineProps({ canLogin: Boolean, canRegister: Boolean });

const { ctaPanel, ctaOrbs } = useScrollOrbs();
</script>

<template>
    <section class="wrap pb-20">
        <div ref="ctaPanel" class="cta relative overflow-hidden px-8 py-16 sm:px-14 sm:py-20">
            <svg
                class="pointer-events-none absolute -right-16 top-1/2 hidden -translate-y-1/2 overflow-visible md:block"
                width="420"
                height="400"
                viewBox="0 0 400 380"
                aria-hidden="true"
            >
                <g style="isolation: isolate">
                    <circle
                        v-for="(o, i) in orbs"
                        :key="o.key"
                        :ref="(el) => (ctaOrbs[i] = el)"
                        class="screen"
                        :cx="o.x"
                        :cy="o.y"
                        r="100"
                        :fill="o.color"
                    />
                </g>
            </svg>

            <div class="relative max-w-lg">
                <h2 class="display text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                    <span class="grad-text">Mulai dari</span> satu pertanyaan.
                </h2>
                <p class="muted mt-3 text-lg">Daftar, jawab pertanyaannya, lalu baca hasilnya.</p>

                <div class="mt-8">
                    <AuthCta class="btn btn-violet" :can-login="canLogin" :can-register="canRegister" />
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.cta {
    --fg: #ffffff;
    --muted: rgb(255 255 255 / 0.82);
    color: #ffffff;
    background: linear-gradient(135deg, #1e1b4b 0%, #3730a3 50%, #7c3aed 80%, #be185d 100%);
    border-radius: 32px;
    box-shadow: 0 30px 60px -30px rgb(55 48 163 / 0.7);
}
.cta .screen { mix-blend-mode: screen; }
.cta .grad-text { background-image: linear-gradient(90deg, #fde68a, #fda4af 55%, #93c5fd); }
.cta :focus-visible { outline-color: #ffffff; }
</style>

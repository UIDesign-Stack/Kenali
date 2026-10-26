<script setup>
import { pillars } from '@/Data/landing';
import { useScrollOrbs } from '@/Composables/useScrollOrbs';

const { fly, dots } = useScrollOrbs();
</script>

<template>
    <section id="tiga-hal" class="wrap scroll-mt-16 pb-24 pt-4">
        <h2 class="display max-w-xl text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
            <span class="grad-text">Tiga hal</span> yang kami baca dari dirimu
        </h2>
        <p class="muted mt-4 max-w-lg text-lg leading-relaxed">
            Satu saja tidak cukup. Minat tanpa bakat cepat bosan, bakat tanpa minat cepat lelah. Karena itu ketiganya
            dilihat bersama.
        </p>

        <div class="mt-12 grid gap-6 md:grid-cols-3">
            <article v-for="(p, i) in pillars" :key="p.title" class="pillar-card" :style="{ '--c': p.color }">
                <div class="flex items-center gap-4">

                    <span
                        :ref="(el) => (dots[i] = el)"
                        class="big-dot"
                        :class="{ 'is-ring': fly }"
                        :style="{ '--c': p.color, background: fly ? 'transparent' : p.color }"
                        aria-hidden="true"
                    ></span>
                    <h3 class="display text-4xl font-extrabold">{{ p.title }}</h3>
                </div>
                <p class="muted mt-5 text-[17px] leading-relaxed">{{ p.text }}</p>
                <p class="quote display mt-6 text-xl font-bold leading-snug">&ldquo;{{ p.q }}&rdquo;</p>
            </article>
        </div>
    </section>
</template>

<style scoped>
.pillar-card {
    padding: 2rem;
    background: color-mix(in srgb, var(--c) 12%, transparent);
    border-radius: 28px;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 35%, transparent);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.pillar-card:hover {
    transform: translateY(-4px);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 55%, transparent),
        0 24px 40px -24px color-mix(in srgb, var(--c) 70%, transparent);
}
.quote { padding-left: 1rem; border-left: 4px solid var(--c); }
.big-dot { flex: none; width: 3.25rem; height: 3.25rem; border-radius: 50%; }
.big-dot.is-ring { box-shadow: inset 0 0 0 2px var(--c); }
@media (prefers-reduced-motion: reduce) { .pillar-card { transition: none; } }
</style>

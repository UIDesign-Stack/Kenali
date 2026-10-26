<script setup>
import { orbs } from '@/Data/landing';
import { useScrollOrbs } from '@/Composables/useScrollOrbs';

const props = defineProps({
    counts: { type: Object, required: true },
    top: Object,
});

const { heroLayers, heroCore } = useScrollOrbs();

const scaleOf = (key) => 0.6 + (props.counts[key] || 0) * 0.16;
</script>

<template>
    <div>
        <svg
            viewBox="0 40 400 320"
            class="mx-auto block w-full max-w-[460px] overflow-visible lg:mx-0"
            role="img"
            aria-label="Tiga lingkaran warna beririsan yang mewakili minat, bakat, dan karakter. Ukurannya bertambah sesuai jumlah ciri yang kamu pilih."
        >
            <g v-for="(o, i) in orbs" :key="o.key" :ref="(el) => (heroLayers[i] = el)" class="layer">
                <g class="enter" :style="{ '--d': i * 160 + 'ms' }">
                    <circle
                        class="orb"
                        :cx="o.x"
                        :cy="o.y"
                        r="85"
                        :fill="o.color"
                        :style="{ transform: 'scale(' + scaleOf(o.key) + ')' }"
                    />
                </g>
            </g>
            <circle
                ref="heroCore"
                cx="200"
                cy="190"
                r="5"
                fill="#ffffff"
                stroke="#1e1b4b"
                stroke-width="2"
                aria-hidden="true"
            />
        </svg>

        <ul class="mx-auto mt-3 flex max-w-[460px] flex-wrap justify-center gap-x-6 gap-y-1 text-sm font-semibold lg:mx-0 lg:justify-start">
            <li v-for="o in orbs" :key="o.key" class="flex items-center gap-2">
                <span class="dot" :style="{ background: o.dot }"></span>
                {{ o.label }}
                <span class="muted tabular-nums">{{ counts[o.key] }} dipilih</span>
            </li>
        </ul>

        <p class="mt-4 max-w-sm text-center text-[15px] leading-snug lg:text-left" aria-live="polite">
            <template v-if="top">
                Titik temu ketiganya: <strong>{{ top.name }}</strong
                >, kecocokan {{ top.pct }}%.
            </template>
            <template v-else>Pilih beberapa ciri di bawah untuk melihat titik temunya.</template>
        </p>
    </div>
</template>

<style scoped>
.layer { mix-blend-mode: multiply; }
.enter {
    transform-box: fill-box;
    transform-origin: center;
    animation: enter 0.9s cubic-bezier(0.2, 0.8, 0.2, 1) both;
    animation-delay: var(--d, 0ms);
}
@keyframes enter {
    from { opacity: 0; transform: scale(0.4); }
    to { opacity: 1; transform: none; }
}
.orb {
    opacity: 0.9;
    transform-box: fill-box;
    transform-origin: center;
    transition: transform 0.6s cubic-bezier(0.34, 1.4, 0.64, 1);
}
@media (prefers-reduced-motion: reduce) {
    .enter { animation: none; }
    .orb { transition: none; }
}
</style>

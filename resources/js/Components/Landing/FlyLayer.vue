<script setup>
import { orbs } from '@/Data/landing';
import { useScrollOrbs } from '@/Composables/useScrollOrbs';

const { flyOrbs, coreDot } = useScrollOrbs();
</script>

<template>
    <svg class="fly-layer" aria-hidden="true">
        <g style="isolation: isolate">
            <circle
                v-for="(o, i) in orbs"
                :key="o.key"
                :ref="(el) => (flyOrbs[i] = el)"
                class="fly-orb"
                r="1"
                :fill="o.color"
                style="transform: translate(-200px, -200px)"
            />
        </g>
        <circle
            ref="coreDot"
            r="4"
            fill="#ffffff"
            stroke="#1e1b4b"
            stroke-width="1.5"
            style="opacity: 0; transform: translate(-200px, -200px)"
        />
    </svg>
</template>

<style scoped>
.fly-layer { position: fixed; inset: 0; z-index: 0; width: 100%; height: 100%; pointer-events: none; }
.fly-orb { opacity: 0.9; mix-blend-mode: multiply; }
</style>

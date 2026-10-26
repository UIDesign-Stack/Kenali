<script setup>
import { groups } from '@/Data/landing';

defineProps({ picked: { type: Array, required: true } });
defineEmits(['toggle']);
</script>

<template>
    <div class="card">
        <h2 class="display text-2xl font-bold">Coba dulu. Pilih yang paling kamu banget.</h2>

        <div class="mt-5 space-y-5">
            <div v-for="g in groups" :key="g.key">
                <p class="mb-2 flex items-center gap-2 text-sm font-bold">
                    <span class="dot" :style="{ background: g.color }"></span>
                    {{ g.label }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="t in g.tags"
                        :key="t.id"
                        type="button"
                        class="chip"
                        :class="{ 'is-on': picked.includes(t.id) }"
                        :style="{ '--c': g.color }"
                        :aria-pressed="picked.includes(t.id)"
                        @click="$emit('toggle', t.id)"
                    >
                        {{ t.label }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.chip {
    padding: 0.45rem 1rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--fg);
    background: color-mix(in srgb, var(--c) 14%, #ffffff);
    border-radius: 999px;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 40%, transparent);
    transition: background-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease;
}
.chip:hover { background: color-mix(in srgb, var(--c) 26%, #ffffff); transform: translateY(-1px); }
.chip.is-on { color: var(--fg); background: var(--c); font-weight: 700; box-shadow: 0 8px 16px -6px var(--c); }
@media (prefers-reduced-motion: reduce) { .chip { transition: none; } }
</style>

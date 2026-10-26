<script setup>
defineProps({ ranked: { type: Array, required: true } });
</script>

<template>
    <div class="card">
        <h2 class="display text-2xl font-bold">Pilihan yang paling cocok sejauh ini</h2>

        <TransitionGroup tag="ul" name="rank" class="mt-5 space-y-4">
            <li v-for="c in ranked" :key="c.name">
                <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm font-semibold">
                    <span>{{ c.name }}</span>
                    <span class="tabular-nums">{{ c.pct }}%</span>
                </div>
                <div class="track">
                    <div class="fill" :style="{ width: c.pct + '%', background: c.color }"></div>
                </div>
            </li>
        </TransitionGroup>

        <p class="muted mt-5 max-w-sm text-xs leading-relaxed">
            Ini simulasi singkat. Tes yang sebenarnya memakai lebih banyak pertanyaan dan pembobotan yang lebih rinci.
        </p>
    </div>
</template>

<style scoped>
.track { height: 10px; background: rgb(30 27 75 / 0.09); border-radius: 999px; overflow: hidden; }
.fill { height: 100%; border-radius: 999px; transition: width 0.6s cubic-bezier(0.2, 0.8, 0.2, 1); }
.rank-move { transition: transform 0.55s cubic-bezier(0.2, 0.8, 0.2, 1); }
@media (prefers-reduced-motion: reduce) {
    .fill, .rank-move { transition: none; }
}
</style>

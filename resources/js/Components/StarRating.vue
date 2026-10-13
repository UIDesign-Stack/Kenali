<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Number, default: 0 },
    readonly: { type: Boolean, default: false },
    size: { type: String, default: 'text-xl' },
});

const emit = defineEmits(['update:modelValue']);

const stars = [1, 2, 3, 4, 5];
const rounded = computed(() => Math.round(props.modelValue));

const ratingText = computed(() => {
    const value = Number(props.modelValue) || 0;
    return Number.isInteger(value) ? String(value) : value.toFixed(1);
});

function select(value) {
    if (!props.readonly) emit('update:modelValue', value);
}
</script>

<template>

    <div
        v-if="readonly"
        class="inline-flex items-center gap-0.5"
        :class="size"
        role="img"
        :aria-label="`Rating ${ratingText} dari 5`"
    >
        <span
            v-for="n in stars"
            :key="n"
            :class="n <= rounded ? 'text-amber-400' : 'text-gray-300'"
            aria-hidden="true"
        >★</span>
    </div>

    <div
        v-else
        class="inline-flex items-center gap-0.5"
        :class="size"
        role="radiogroup"
        aria-label="Beri rating"
    >
        <button
            v-for="n in stars"
            :key="n"
            type="button"
            role="radio"
            :aria-checked="n === modelValue"
            @click="select(n)"
            :aria-label="`${n} bintang`"
            class="leading-none transition hover:scale-110"
            :class="n <= modelValue ? 'text-amber-400' : 'text-gray-300 hover:text-amber-300'"
        >★</button>
    </div>
</template>

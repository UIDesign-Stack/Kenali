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

function select(value) {
    if (!props.readonly) emit('update:modelValue', value);
}
</script>

<template>
    <div class="inline-flex items-center gap-0.5" :class="size" role="img" :aria-label="`Rating ${modelValue} dari 5`">
        <template v-if="readonly">
            <span
                v-for="n in stars"
                :key="n"
                :class="n <= rounded ? 'text-amber-400' : 'text-gray-300'"
                aria-hidden="true"
            >★</span>
        </template>
        <template v-else>
            <button
                v-for="n in stars"
                :key="n"
                type="button"
                @click="select(n)"
                :aria-label="`${n} bintang`"
                class="leading-none transition hover:scale-110"
                :class="n <= modelValue ? 'text-amber-400' : 'text-gray-300 hover:text-amber-300'"
            >★</button>
        </template>
    </div>
</template>
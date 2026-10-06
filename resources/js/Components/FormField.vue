<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    error: { type: String, default: undefined },
});
</script>

<template>
    <div>
        <label :for="id" class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>

        <slot :invalid="!!error" :describedby="error ? `${id}-error` : undefined" />

        <p v-if="error" :id="`${id}-error`" class="text-xs text-red-600 mt-1" role="alert">{{ error }}</p>
    </div>
</template>

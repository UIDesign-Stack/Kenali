<script setup>
defineProps({ laravelVersion: String, phpVersion: String });

const year = new Date().getFullYear();
</script>

<template>
    <footer class="site-footer">
        <div class="wrap muted flex flex-col gap-2 py-6 text-sm sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ year }} Kenali. Kenali dirimu, temukan arahmu.</p>
            <p v-if="laravelVersion">Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})</p>
        </div>
    </footer>
</template>

<style scoped>
.site-footer { position: relative; background: rgb(255 255 255 / 0.6); }
</style>

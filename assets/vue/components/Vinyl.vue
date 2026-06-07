<template>
    <div class="vinyl" :class="{ paused: !playing }" :style="{ '--size': size + 'px' }">
        <div class="center">{{ label }}</div>
    </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{ size?: number; playing?: boolean; label?: string }>(), {
    size: 200,
    playing: true,
    label: '?',
});
</script>

<template>
    <span class="sb-icon" v-html="markup"></span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { SB_ICONS } from './icons';

const props = defineProps<{ name: string }>();

const markup = computed(() => SB_ICONS[props.name] ?? '');
</script>

<style lang="scss" scoped>
.sb-icon {
    display: inline-flex;
    line-height: 0;
}
</style>

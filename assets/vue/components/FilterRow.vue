<template>
    <div class="mb-16">
        <div class="uc muted" style="margin-bottom: 8px;">{{ label }}</div>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <span
                v-for="item in items"
                :key="item.value"
                class="chip"
                :class="{ checked: selected.includes(item.value) }"
                @click="$emit('toggle', item.value)"
            >
                <span class="dot"><SbIcon name="Check" /></span>
                {{ item.label }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import SbIcon from './SbIcon.vue';
import type { FilterRowItem } from './types';

defineProps<{
    label: string;
    items: FilterRowItem[];
    selected: Array<string | number>;
}>();

defineEmits<{
    (e: 'toggle', value: string | number): void;
}>();
</script>

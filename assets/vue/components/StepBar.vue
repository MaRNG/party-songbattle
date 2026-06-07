<template>
    <div>
        <div class="step-bar">
            <div
                v-for="(step, index) in steps"
                :key="index"
                class="seg"
                :class="{ done: index < currentIdx, active: index === currentIdx }"
            >
                <div class="fill" :style="index === currentIdx && fillPercent !== null ? { width: fillPercent + '%' } : undefined" />
            </div>
        </div>
        <div class="step-labels">
            <span v-for="(step, index) in steps" :key="index" :class="{ now: index === currentIdx }">
                {{ step }}{{ t.seconds }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Strings } from '../composables/i18n';

withDefaults(defineProps<{
    steps: number[];
    currentIdx: number;
    t: Strings;
    fillPercent?: number | null;
}>(), {
    fillPercent: null,
});
</script>

<template>
    <div class="track-card" style="position: relative;">
        <div class="track-logo">♪</div>
        <div class="cover" />
        <div class="meta">
            <p class="title">{{ trackName }}</p>
            <p class="artist"><template v-if="prefix">{{ prefix }} </template> {{ artistName }}</p>
            <div class="tag-row">
                <slot name="tags" />
            </div>
        </div>
        <div v-if="$slots.controls" class="ctrls">
            <slot name="controls" />
        </div>
    </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
    trackName: string;
    artistName: string;
    prefix?: string;
}>(), {
    prefix: '',
});
</script>

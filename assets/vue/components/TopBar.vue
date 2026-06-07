<template>
    <div class="topbar">
        <div class="brand">
            <div class="brand-mark"><SbIcon name="LogoMark" /></div>
            <div class="brand-text">
                {{ t.brand }}
                <small>{{ sub ?? t.brandSub }}</small>
            </div>
        </div>
        <div class="chrome-actions">
            <slot name="right" />
            <button class="icon-btn" title="Language" @click="$emit('update:lang', lang === 'cs' ? 'en' : 'cs')">
                <span class="mono" style="font-size: 11px; font-weight: 700;">{{ lang.toUpperCase() }}</span>
            </button>
            <button class="icon-btn" title="Theme" @click="$emit('update:theme', theme === 'dark' ? 'light' : 'dark')">
                <SbIcon :name="theme === 'dark' ? 'Sun' : 'Moon'" />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import SbIcon from './SbIcon.vue';
import type { Strings, Lang } from '../composables/i18n';

defineProps<{
    t: Strings;
    sub?: string;
    lang: Lang;
    theme: 'dark' | 'light';
}>();

defineEmits<{
    (e: 'update:lang', value: Lang): void;
    (e: 'update:theme', value: 'dark' | 'light'): void;
}>();
</script>

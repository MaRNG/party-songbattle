<template>
    <div style="max-width: 920px; margin: 0 auto; text-align: center; padding-top: 8px;">
        <div class="eyebrow" style="display: inline-flex;">{{ t.landing_eyebrow }}</div>
        <h1 class="display" style="white-space: pre-line; font-size: 44px; margin: 8px 0 6px;">{{ t.landing_title }}</h1>
        <p class="lead" style="margin: 8px auto 0;">{{ t.landing_lead }}</p>

        <div style="display: flex; justify-content: center; gap: 28px; margin: 20px 0 18px; align-items: center; height: 180px;">
            <Vinyl :size="110" label="?" />
            <Vinyl :size="160" label="?" />
            <Vinyl :size="110" label="?" />
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; width: 100%; max-width: 380px; margin: 0 auto;">
            <button class="btn btn-primary btn-block" @click="$emit('create')">
                <SbIcon name="Plus" /> {{ t.cta_create }}
            </button>
            <div style="display: flex; align-items: center; gap: 10px; color: var(--dim); font-size: 12px;">
                <div style="flex: 1; height: 1px; background: var(--border);" />
                <span class="mono uc">{{ t.or }}</span>
                <div style="flex: 1; height: 1px; background: var(--border);" />
            </div>
            <input v-model="name" class="input" :placeholder="lang === 'cs' ? 'Tvoje jméno' : 'Your name'" />
            <div style="display: flex; gap: 8px;">
                <input v-model="code" class="input input-mono" :placeholder="t.code_placeholder" style="flex: 1; text-transform: uppercase;" />
                <button class="btn btn-ghost" :disabled="!canJoin" @click="join">{{ t.cta_join }}</button>
            </div>
        </div>

        <div style="display: flex; gap: 8px; margin: 20px auto 0; justify-content: center; flex-wrap: wrap;">
            <span class="pill">🎮 {{ t.landing_play_solo }}</span>
            <span class="pill">🎉 {{ t.landing_play_party }}</span>
            <span class="pill">🎧 Spotify</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import Vinyl from '../components/Vinyl.vue';
import SbIcon from '../components/SbIcon.vue';
import type { Strings, Lang } from '../composables/i18n';

defineProps<{ t: Strings; lang: Lang }>();

const emit = defineEmits<{
    (e: 'create'): void;
    (e: 'join', hash: string, name: string): void;
}>();

const name = ref('');
const code = ref('');

const canJoin = computed(() => name.value.trim() !== '' && code.value.trim() !== '');

function join(): void {
    if (!canJoin.value)
    {
        return;
    }

    emit('join', code.value.trim().toUpperCase(), name.value.trim());
}
</script>

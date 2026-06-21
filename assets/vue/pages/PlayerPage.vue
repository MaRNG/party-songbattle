<template>
    <div v-if="state" style="max-width: 1040px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
            <div class="eyebrow">{{ t.player_eyebrow }}</div>
            <span class="mono small muted">{{ t.your_rank }}: #{{ myRank }} · {{ me?.score ?? 0 }} {{ t.points }}</span>
        </div>

        <div class="grid-2">
            <div class="col" style="align-items: center; justify-content: center; display: flex;">
                <div style="display: flex; justify-content: center; padding: 14px 0 8px;">
                    <Vinyl :size="240" label="?" :playing="state.isPlaying" />
                </div>

                <div class="card" style="width: 100%;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span class="mono uc muted">{{ t.elapsed }}</span>
                        <span class="timer" style="color: var(--neon-1);">
                            {{ state.elapsedSeconds.toFixed(1) }}<span class="small">{{ t.seconds }}</span> / {{ stepLimit }}{{ t.seconds }}
                        </span>
                    </div>
                    <StepBar :steps="STEPS" :current-idx="state.stepIndex" :t="t" :fill-percent="fillPercent" />
                </div>
            </div>

            <div class="col">
                <div class="turn-card">
                    <div class="av" :style="{ background: me?.color }">{{ me?.initials }}</div>
                    <div style="flex: 1;">
                        <div class="mono uc" style="color: var(--neon-1); font-size: 11px;">{{ t.on_turn }}</div>
                        <div style="font-weight: 700;">{{ t.your_turn }}</div>
                    </div>
                    <div class="eq"><span /><span /><span /><span /><span /></div>
                </div>

                <div>
                    <div class="mono uc muted" style="margin-bottom: 8px;">{{ t.your_guess }}</div>
                    <GuessInput :t="t" :session="session" @guess="onGuess" />
                    <div v-if="feedback" class="mono small dim" style="margin-top: 6px;">{{ feedback }}</div>
                </div>

                <div class="card card-tight">
                    <div class="mono uc muted" style="margin-bottom: 8px;">{{ t.streak }}</div>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <span :style="{ fontSize: '28px', fontWeight: 700, color: (me?.streak ?? 0) > 0 ? 'var(--neon-1)' : 'var(--muted)' }">{{ me?.streak ?? 0 }}×</span>
                        <span class="mono small muted">{{ (me?.streak ?? 0) >= 2 ? '🔥' : '' }}</span>
                        <div style="flex: 1;" />
                        <span v-if="state.isPlaying" class="pill live"><span class="dot" />{{ t.playing }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import Vinyl from '../components/Vinyl.vue';
import GuessInput from '../components/GuessInput.vue';
import StepBar from '../components/StepBar.vue';
import { STEPS, type Strings } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';

const props = defineProps<{ t: Strings; session: GameSession }>();

const emit = defineEmits<{
    (e: 'guess', text: string): void;
}>();

const state = computed(() => props.session.state.value);

const me = computed(() => state.value?.players.find((player) => player.isViewer) ?? null);

const myRank = computed(() => {
    if (!state.value || !me.value)
    {
        return '—';
    }

    const sorted = [...state.value.players].sort((a, b) => b.score - a.score);

    return sorted.findIndex((player) => player.id === me.value!.id) + 1;
});

const stepLimit = computed(() => STEPS[state.value?.stepIndex ?? 0]);

const fillPercent = computed(() => {
    if (!state.value)
    {
        return null;
    }

    return Math.min(100, (state.value.elapsedSeconds / stepLimit.value) * 100);
});

const feedback = ref('');

function onGuess(text: string): void {
    feedback.value = '';
    emit('guess', text);
}
</script>

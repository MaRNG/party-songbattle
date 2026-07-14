<template>
    <div v-if="state" style="max-width: 1040px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
            <div class="eyebrow">{{ t.player_eyebrow }}</div>
            <span class="mono small muted">{{ t.your_rank }}: #{{ myRank }} · {{ me?.score ?? 0 }} {{ t.points }}</span>
        </div>

        <!-- Guessing goes first, above the vinyl/player visual — on mobile that's the
        first thing on screen instead of something to scroll past on every song. -->
        <div class="turn-card">
            <div class="av" :style="{ background: me?.color }">{{ me?.initials }}</div>
            <div style="flex: 1;">
                <div class="mono uc" style="color: var(--neon-1); font-size: 11px;">{{ t.on_turn }}</div>
                <div style="font-weight: 700;">{{ canGuess ? t.your_turn : t.waiting_turn }}</div>
            </div>
            <div class="eq"><span /><span /><span /><span /><span /></div>
        </div>

        <div v-if="canGuess" class="mt-12">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <span class="mono uc muted">{{ t.your_guess }}</span>
                <span v-if="state?.mode === 'all' && me?.attemptsRemaining !== null && me?.attemptsRemaining !== undefined" class="mono small muted">
                    {{ t.attempts_remaining(me.attemptsRemaining) }}
                </span>
            </div>
            <GuessInput :t="t" :session="session" @guess="(trackId) => emit('guess', trackId)" />
            <button v-if="state?.mode === 'all'" class="btn btn-ghost btn-sm mt-8" @click="pass">
                {{ t.pass_btn }}
            </button>
        </div>
        <div v-else class="card card-tight muted center mt-12">
            {{ waitingText }}
        </div>

        <div class="grid-2" style="margin-top: 14px;">
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
                <div class="card card-tight">
                    <div class="mono uc muted" style="margin-bottom: 8px;">{{ t.streak }}</div>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <span :style="{ fontSize: '28px', fontWeight: 700, color: (me?.streak ?? 0) > 0 ? 'var(--neon-1)' : 'var(--muted)' }">{{ me?.streak ?? 0 }}×</span>
                        <span class="mono small muted">{{ (me?.streak ?? 0) >= 2 ? '🔥' : '' }}</span>
                        <div style="flex: 1;" />
                        <span v-if="state.isPlaying" class="pill live"><span class="dot" />{{ t.playing }}</span>
                    </div>
                </div>

                <div v-if="state.showLeaderboardToPlayers" class="card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <strong>{{ t.leaderboard }}</strong>
                        <span class="mono small muted">{{ state.players.length }} {{ t.players_connected }}</span>
                    </div>
                    <div v-if="leaderboard.length === 0" class="muted">{{ t.nobody_yet }}</div>
                    <div
                        v-for="(player, index) in leaderboard"
                        :key="player.id"
                        class="player-row"
                        :class="{ current: player.isViewer, guessed: index === 0 && player.score > 0 }"
                    >
                        <div class="mono small" style="width: 18px; color: var(--dim);">{{ index + 1 }}</div>
                        <div class="av" :style="{ background: player.color }">{{ player.initials }}</div>
                        <div class="name">
                            {{ player.name }}
                            <span v-if="player.isCurrentTurn" class="pill live" style="margin-left: 8px;">
                                <span class="dot" />{{ t.on_turn }}
                            </span>
                        </div>
                        <div class="meta">{{ player.score }} {{ t.points }} · {{ player.guesses }}×</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import Vinyl from '../components/Vinyl.vue';
import GuessInput from '../components/GuessInput.vue';
import StepBar from '../components/StepBar.vue';
import { STEPS, type Strings } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';

const props = defineProps<{ t: Strings; session: GameSession }>();

const emit = defineEmits<{
    (e: 'guess', trackId: number): void;
}>();

const state = computed(() => props.session.state.value);

const me = computed(() => state.value?.players.find((player) => player.isViewer) ?? null);

const canGuess = computed(() => {
    if (state.value?.mode === 'robin')
    {
        return me.value?.isCurrentTurn === true;
    }

    if (state.value?.mode === 'all')
    {
        return me.value?.answeredCorrectly !== true
            && me.value?.hasPassed !== true
            && (me.value?.attemptsRemaining ?? 1) > 0;
    }

    return true;
});

const waitingText = computed(() => {
    if (state.value?.mode === 'all')
    {
        if (me.value?.answeredCorrectly)
        {
            return props.t.answered_waiting;
        }

        if (me.value?.hasPassed)
        {
            return props.t.passed_waiting;
        }
    }

    return props.t.waiting_turn;
});

async function pass(): Promise<void> {
    await props.session.passRound().catch(() => undefined);
}

const leaderboard = computed(() =>
    [...(state.value?.players ?? [])].sort((a, b) => b.score - a.score),
);

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

</script>

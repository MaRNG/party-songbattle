<template>
    <div v-if="state" style="max-width: 920px; margin: 0 auto;">
        <div class="center">
            <div class="eyebrow" style="justify-content: center; display: inline-flex;">{{ t.results_sub }}</div>
            <h1 class="display" style="display: inline-flex; align-items: center; gap: 12px; font-size: 44px;">
                <SbIcon name="Trophy" /> {{ t.results_title }}
            </h1>
        </div>

        <div class="podium" style="margin-top: 24px;">
            <div v-if="second" class="step silver">
                <div class="av" :style="{ background: second.color }">{{ second.initials }}</div>
                <div class="nm">{{ second.name }}</div>
                <div class="pts">{{ second.score }}</div>
                <div class="rank">2ND</div>
            </div>
            <div v-if="first" class="step gold">
                <SbIcon name="Crown" />
                <div class="av" :style="{ background: first.color, marginTop: '6px' }">{{ first.initials }}</div>
                <div class="nm">{{ first.name }}</div>
                <div class="pts" style="color: var(--neon-1);">{{ first.score }}</div>
                <div class="rank">1ST</div>
            </div>
            <div v-if="third" class="step bronze">
                <div class="av" :style="{ background: third.color }">{{ third.initials }}</div>
                <div class="nm">{{ third.name }}</div>
                <div class="pts">{{ third.score }}</div>
                <div class="rank">3RD</div>
            </div>
        </div>

        <div class="row mt-16" style="gap: 12px;">
            <div class="stat"><div class="label">{{ t.guessed }}</div><div class="value">{{ totalGuesses }}</div></div>
            <div class="stat"><div class="label">{{ t.best_streak }}</div><div class="value" style="color: var(--neon-1);">{{ bestStreak }}<span class="unit">×</span></div></div>
            <div class="stat"><div class="label">{{ t.total_songs }}</div><div class="value">{{ state.totalTracks }}</div></div>
        </div>

        <div class="card mt-16">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <strong>{{ t.leaderboard }}</strong>
            </div>
            <div
                v-for="(player, index) in ranked"
                :key="player.id"
                class="player-row"
                :class="{ current: player.isViewer }"
            >
                <div class="mono small" :style="{ width: '22px', color: index < 3 ? 'var(--neon-1)' : 'var(--dim)', fontWeight: 700 }">{{ index + 1 }}</div>
                <div class="av" :style="{ background: player.color }">{{ player.initials }}</div>
                <div class="name">{{ player.name }}</div>
                <div class="meta">{{ player.score }} {{ t.points }}</div>
            </div>
        </div>

        <button class="btn btn-primary btn-block mt-16" @click="$emit('restart')">
            <SbIcon name="PlayFill" /> {{ t.play_again }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SbIcon from '../components/SbIcon.vue';
import { type Strings } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';

const props = defineProps<{ t: Strings; session: GameSession }>();

defineEmits<{
    (e: 'restart'): void;
}>();

const state = computed(() => props.session.state.value);

const ranked = computed(() => [...(state.value?.players ?? [])].sort((a, b) => b.score - a.score));

const first = computed(() => ranked.value[0] ?? null);
const second = computed(() => ranked.value[1] ?? null);
const third = computed(() => ranked.value[2] ?? null);

const totalGuesses = computed(() => ranked.value.reduce((sum, player) => sum + player.guesses, 0));

const bestStreak = computed(() => ranked.value.reduce((max, player) => Math.max(max, player.streak), 0));
</script>

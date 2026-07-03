<template>
    <div style="max-width: 720px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
            <div class="eyebrow" style="--neon-1: #ff7676;">{{ t.missed_eyebrow }}</div>
        </div>

        <div class="card" style="position: relative; overflow: hidden; text-align: center; padding: 32px 28px; border-color: rgba(255,118,118,0.4);">
            <div class="sb-vinyl-shake" style="display: inline-block; position: relative; margin-bottom: 16px;">
                <div class="vinyl paused" style="--size: 150px; filter: grayscale(0.6) brightness(0.7);">
                    <div class="center"><span class="sb-x-flash">×</span></div>
                </div>
                <svg viewBox="0 0 200 200" style="position: absolute; inset: 0; pointer-events: none; width: 100%; height: 100%;">
                    <path class="sb-crack" d="M40 70 L 100 100 L 70 130 L 130 145 L 165 110" fill="none" stroke="#ff7676" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    <path class="sb-crack" style="animation-delay: 0.3s;" d="M100 100 L 95 105" fill="none" stroke="#ff7676" stroke-width="3" stroke-linecap="round" />
                </svg>
            </div>

            <div class="eyebrow" style="justify-content: center; display: inline-flex; --neon-1: #ff7676;">{{ t.no_points }}</div>
            <h1 class="display" style="font-size: 36px; margin: 8px 0 4px;">{{ t.missed_title }}</h1>
            <p class="lead" style="margin: 6px auto 18px; color: var(--muted);">{{ t.missed_sub }}</p>

            <SpotifyCard v-if="track" :track-name="track.trackName" :artist-name="track.artistName" :prefix="t.by">
                <template v-if="canPlayFullTrack" #controls>
                    <button class="spfy-btn play" @click="toggleFullTrack">
                        <SbIcon :name="spotify.isPlaying.value ? 'Pause' : 'PlayFill'" />
                    </button>
                </template>
            </SpotifyCard>
        </div>

        <div class="card card-tight mt-12" style="display: flex; align-items: center; gap: 10px;">
            <div style="font-size: 22px;">💪</div>
            <div style="font-size: 13px; flex: 1;">{{ t.keep_going }}</div>
        </div>

        <button v-if="session.isMaster.value" class="btn btn-primary btn-block mt-12" @click="emit('continue')">
            {{ t.continue_btn }} →
        </button>
        <div v-else class="mono small muted center mt-12">{{ t.waiting_master }}</div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import SpotifyCard from '../components/SpotifyCard.vue';
import SbIcon from '../components/SbIcon.vue';
import { type Strings } from '../composables/i18n';
import type { TrackInfoDto } from '../api/client';
import type { GameSession } from '../composables/useGameSession';
import { useFullTrackPlayback } from '../composables/useFullTrackPlayback';

const props = defineProps<{
    t: Strings;
    track: TrackInfoDto | null;
    session: GameSession;
}>();

const emit = defineEmits<{
    (e: 'continue'): void;
}>();

const trackRef = computed(() => props.track);
const { spotify, canPlayFullTrack, toggleFullTrack } = useFullTrackPlayback(trackRef, props.session);
</script>

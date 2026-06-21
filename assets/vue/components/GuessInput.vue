<template>
    <div class="suggest-wrap">
        <input
            v-model="guess"
            class="input"
            :placeholder="t.type_a_song"
            style="padding-right: 100px; font-size: 16px;"
            @keydown.enter="onEnter"
            @keydown.down.prevent="moveActive(1)"
            @keydown.up.prevent="moveActive(-1)"
            @keydown.esc="closeSuggestions"
            @blur="onBlur"
        />
        <button
            class="btn btn-primary btn-sm"
            :disabled="!guess.trim()"
            style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%);"
            @click="submit"
        >
            <SbIcon name="Send" /> {{ t.submit }}
        </button>

        <ul v-if="suggestions.length > 0" class="suggest-list">
            <li
                v-for="(item, index) in suggestions"
                :key="`${item.trackName}-${item.artistName}`"
                class="suggest-item"
                :class="{ active: index === activeIndex }"
                @mousedown.prevent="selectSuggestion(item)"
            >
                <span class="suggest-track">{{ item.trackName }}</span>
                <span class="suggest-artist">{{ item.artistName }}</span>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import SbIcon from './SbIcon.vue';
import { SongBattleApi, type TrackInfoDto } from '../api/client';
import type { Strings } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';

const props = defineProps<{ t: Strings; session: GameSession }>();

const emit = defineEmits<{
    (e: 'guess', text: string): void;
}>();

const guess = ref('');
const suggestions = ref<TrackInfoDto[]>([]);
const activeIndex = ref(-1);

let debounceHandle: ReturnType<typeof setTimeout> | null = null;

watch(guess, (value) => {
    activeIndex.value = -1;

    if (debounceHandle !== null)
    {
        clearTimeout(debounceHandle);
        debounceHandle = null;
    }

    const trimmed = value.trim();

    if (trimmed.length < 2)
    {
        suggestions.value = [];

        return;
    }

    debounceHandle = setTimeout(() => {
        void fetchSuggestions(trimmed);
    }, 250);
});

async function fetchSuggestions(query: string): Promise<void> {
    const hash = props.session.game.value?.hash;
    const token = props.session.player.value?.token;

    if (!hash || !token)
    {
        return;
    }

    try
    {
        const result = await SongBattleApi.suggestTracks(hash, token, query);

        // Discard a stale response if the input changed again while this request was in flight.
        if (guess.value.trim().toLowerCase() !== query.toLowerCase())
        {
            return;
        }

        suggestions.value = result.suggestions;
    }
    catch
    {
        suggestions.value = [];
    }
}

function closeSuggestions(): void {
    suggestions.value = [];
    activeIndex.value = -1;
}

function onBlur(): void {
    // Selecting a suggestion uses @mousedown.prevent so it fires before blur — this just
    // closes the dropdown when focus leaves the input for any other reason.
    closeSuggestions();
}

function moveActive(delta: number): void {
    if (suggestions.value.length === 0)
    {
        return;
    }

    const next = activeIndex.value + delta;

    activeIndex.value = Math.max(0, Math.min(suggestions.value.length - 1, next));
}

function selectSuggestion(item: TrackInfoDto): void {
    guess.value = item.trackName;
    closeSuggestions();
}

function onEnter(): void {
    if (activeIndex.value >= 0 && suggestions.value[activeIndex.value])
    {
        selectSuggestion(suggestions.value[activeIndex.value]);

        return;
    }

    submit();
}

function submit(): void {
    const text = guess.value.trim();

    if (text === '')
    {
        return;
    }

    closeSuggestions();
    emit('guess', text);
    guess.value = '';
}
</script>

<style scoped lang="scss">
.suggest-wrap {
    position: relative;
}

.suggest-list {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    z-index: 40;
    margin: 0;
    padding: 6px;
    list-style: none;
    background: var(--surface);
    border: 1px solid var(--border-strong);
    border-radius: 12px;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    max-height: 240px;
    overflow-y: auto;
}

.suggest-item {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;

    &:hover,
    &.active {
        background: color-mix(in oklab, var(--neon-1) 16%, transparent);
    }
}

.suggest-track {
    font-weight: 600;
}

.suggest-artist {
    color: var(--muted);
    font-size: 12px;
}
</style>

<template>
    <div class="suggest-wrap">
        <input
            v-model="guess"
            class="input guess-field"
            :placeholder="t.type_a_song"
            @keydown.enter="onEnter"
            @keydown.down.prevent="moveActive(1)"
            @keydown.up.prevent="moveActive(-1)"
            @keydown.esc="closeSuggestions"
            @blur="onBlur"
        />
        <button
            class="btn btn-primary btn-sm guess-submit"
            :disabled="selectedId === null"
            @click="submit"
        >
            <SbIcon name="Send" /> <span class="submit-label">{{ t.submit }}</span>
        </button>

        <ul v-if="suggestions.length > 0" class="suggest-list">
            <li
                v-for="(item, index) in suggestions"
                :key="item.id ?? `${item.trackName}-${item.artistName}`"
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
    (e: 'guess', trackId: number): void;
}>();

const guess = ref('');
const suggestions = ref<TrackInfoDto[]>([]);
const activeIndex = ref(-1);
const selectedId = ref<number | null>(null);

let debounceHandle: ReturnType<typeof setTimeout> | null = null;
// Set right before selectSuggestion() writes to `guess.value` so the watcher below
// doesn't immediately clear the selection it just made.
let programmaticChange = false;

watch(guess, (value) => {
    activeIndex.value = -1;

    if (programmaticChange)
    {
        programmaticChange = false;

        return;
    }

    // Any manual edit invalidates a prior pick — the guess must come from a freshly
    // selected suggestion, not leftover text.
    selectedId.value = null;

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
    programmaticChange = true;
    guess.value = `${item.trackName} - ${item.artistName}`;
    selectedId.value = item.id;
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
    if (selectedId.value === null)
    {
        return;
    }

    closeSuggestions();
    emit('guess', selectedId.value);
    guess.value = '';
    selectedId.value = null;
}
</script>

<style scoped lang="scss">
.suggest-wrap {
    position: relative;
    width: 100%;
    max-width: 100%;
}

.guess-field {
    width: 100%;
    padding-right: 100px;
    font-size: 16px;
}

.guess-submit {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
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
    // Safety net so a suggestion with a long, unbreakable word (no spaces — a common
    // shape for artist/track names) never bleeds past the dropdown's own box and off
    // the edge of a narrow phone screen.
    overflow-x: hidden;
}

.suggest-item {
    display: flex;
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

.suggest-track,
.suggest-artist {
    // min-width: 0 overrides the flex-item default of min-width: auto — without it, a
    // long unbroken name refuses to shrink and instead pushes this item (and the
    // dropdown around it) wider than its container.
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.suggest-track {
    font-weight: 600;
    flex: 1 1 auto;
}

.suggest-artist {
    color: var(--muted);
    font-size: 12px;
    flex: 0 1 auto;
    max-width: 45%;
}

@media (max-width: 420px) {
    .guess-field {
        padding-right: 76px;
        font-size: 15px;
    }

    .guess-submit .submit-label {
        display: none;
    }
}
</style>

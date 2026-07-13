import { ref } from 'vue';
import { SongBattleApi } from '../api/client';

const isPlaying = ref(false);
const error = ref<string | null>(null);

let audioEl: HTMLAudioElement | null = null;
let loadedKey: string | null = null;

function ensureAudioElement(): HTMLAudioElement {
    if (audioEl === null)
    {
        audioEl = new Audio();
        audioEl.preload = 'auto';

        audioEl.addEventListener('playing', () => { isPlaying.value = true; });
        audioEl.addEventListener('pause', () => { isPlaying.value = false; });
        audioEl.addEventListener('error', () => {
            error.value = 'Local audio playback failed';
            isPlaying.value = false;
        });
    }

    return audioEl;
}

// A plain <audio> element only needs one play() call inside a real user gesture to be
// "unlocked" for later scripted play() calls (e.g. from a state-poll-driven track change)
// that aren't gestures themselves.
async function activateElement(): Promise<void> {
    const el = ensureAudioElement();

    try
    {
        await el.play();
        el.pause();
    }
    catch
    {
        // Will simply retry on the next real click — nothing to surface to the user yet.
    }
}

function getEstimatedPositionMs(): number {
    return ensureAudioElement().currentTime * 1000;
}

async function pause(): Promise<void> {
    ensureAudioElement().pause();
}

async function resume(): Promise<void> {
    try
    {
        await ensureAudioElement().play();
    }
    catch (err)
    {
        console.error('[local-audio] resume failed', err);
        error.value = 'Local audio resume failed';
    }
}

async function playFromStart(hash: string, token: string, audioTrackId: number, shouldBePlaying: boolean): Promise<void> {
    const key = `${hash}:${audioTrackId}`;
    const el = ensureAudioElement();

    if (loadedKey !== key)
    {
        el.src = SongBattleApi.trackAudioUrl(hash, token, audioTrackId);
        loadedKey = key;
    }

    el.currentTime = 0;

    if (shouldBePlaying)
    {
        await resume();
    }
    else
    {
        el.pause();
    }
}

async function ensureTrackLoaded(hash: string, token: string, audioTrackId: number, shouldBePlaying: boolean): Promise<void> {
    const key = `${hash}:${audioTrackId}`;

    if (loadedKey === key)
    {
        return;
    }

    await playFromStart(hash, token, audioTrackId, shouldBePlaying);
}

export function useLocalAudioPlayer() {
    return {
        isPlaying,
        error,
        playFromStart,
        ensureTrackLoaded,
        pause,
        resume,
        getEstimatedPositionMs,
        activateElement,
    };
}

import { computed, watch, type Ref } from 'vue';
import type { GameSession } from './useGameSession';
import type { TrackInfoDto } from '../api/client';
import { useLocalAudioPlayer } from './useLocalAudioPlayer';

export function useFullTrackPlayback(track: Ref<TrackInfoDto | null>, session: GameSession) {
    const playback = useLocalAudioPlayer();

    const canPlayFullTrack = computed(() => session.isMaster.value && track.value?.audioTrackId != null);

    let hasStartedFullTrack = false;

    watch(track, () => {
        hasStartedFullTrack = false;
    });

    async function toggleFullTrack(): Promise<void> {
        const audioTrackId = track.value?.audioTrackId;
        const hash = session.game.value?.hash;
        const token = session.player.value?.token;

        if (audioTrackId == null || !hash || !token)
        {
            return;
        }

        // Unlike the other call sites of activateElement() (which all have a network
        // await in between), there's nothing here to let its play()+pause() unlock
        // cycle finish before the real playback call below — awaiting it directly
        // avoids a race where that trailing pause() lands right after this function's
        // own play(), silently stopping playback the instant it started.
        await playback.activateElement();

        if (!hasStartedFullTrack)
        {
            await playback.playFromStart(hash, token, audioTrackId, true);
            hasStartedFullTrack = true;

            return;
        }

        if (playback.isPlaying.value)
        {
            await playback.pause();
        }
        else
        {
            await playback.resume();
        }
    }

    return { playback, canPlayFullTrack, toggleFullTrack };
}

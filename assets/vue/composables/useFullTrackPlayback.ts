import { computed, watch, type Ref } from 'vue';
import type { GameSession } from './useGameSession';
import type { TrackInfoDto } from '../api/client';
import { useSpotifyPlayer } from './useSpotifyPlayer';

export function useFullTrackPlayback(track: Ref<TrackInfoDto | null>, session: GameSession) {
    const spotify = useSpotifyPlayer();

    const canPlayFullTrack = computed(() =>
        session.isMaster.value && !!track.value?.spotifyTrackId,
    );

    let hasStartedFullTrack = false;

    watch(track, () => {
        hasStartedFullTrack = false;
    });

    async function toggleFullTrack(): Promise<void> {
        const trackId = track.value?.spotifyTrackId;

        if (!trackId)
        {
            return;
        }

        void spotify.activateElement();

        if (!hasStartedFullTrack)
        {
            const ready = await spotify.ensureReady();

            if (!ready)
            {
                spotify.error.value = 'Spotify player not ready (isReady=false) — could not play the full track';

                return;
            }

            await spotify.playFromStart(trackId, true);
            hasStartedFullTrack = true;

            return;
        }

        if (spotify.isPlaying.value)
        {
            await spotify.pause();
        }
        else
        {
            await spotify.resume();
        }
    }

    return { spotify, canPlayFullTrack, toggleFullTrack };
}

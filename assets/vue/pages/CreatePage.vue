<template>
    <div style="max-width: 920px; margin: 0 auto;">
        <div class="eyebrow">{{ t.create_title }}</div>
        <h2 class="section-title" style="margin-top: 6px;">{{ t.create_sub }}</h2>

        <div class="grid-3" style="gap: 12px; margin-top: 18px;">
            <div
                v-for="card in modeCards"
                :key="card.id"
                class="mode-card"
                :class="{ checked: mode === card.id }"
                @click="mode = card.id"
            >
                <div class="ic"><SbIcon :name="card.icon" /></div>
                <div>
                    <h3>{{ card.title }}</h3>
                    <p>{{ card.desc }}</p>
                </div>
                <div class="radio" />
            </div>
        </div>

        <div class="card mt-16">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 14px;">
                <SbIcon name="Filter" /><strong>{{ t.filters }}</strong>
            </div>

            <FilterRow :label="t.f_decade" :items="decadeItems" :selected="decades" @toggle="toggleDecade" />
            <FilterRow :label="t.f_genre" :items="genreItems" :selected="genres" @toggle="toggleGenre" />
            <FilterRow :label="t.f_country" :items="areaItems" :selected="areas" @toggle="toggleArea" />
        </div>

        <div class="card mt-12 card-glow" style="display: flex; align-items: center; gap: 16px;">
            <div style="flex: 1;">
                <div class="mono uc muted">~ {{ t.songs_in_pool }}</div>
                <div style="font-size: 28px; font-weight: 700; line-height: 1; margin-top: 4px; color: var(--neon-1); min-height: 1em;">
                    <span v-if="optionsLoading" class="sb-spinner" />
                    <template v-else>{{ poolCount }}</template>
                </div>
            </div>
            <input v-model="name" class="input" :placeholder="lang === 'cs' ? 'Tvoje jméno' : 'Your name'" style="max-width: 220px;" />
            <button class="btn btn-primary" :disabled="!canCreate || creating" @click="create">
                <span v-if="creating" class="sb-spinner sb-spinner--btn" />
                <template v-else>{{ t.create_cta }} →</template>
            </button>
        </div>

        <button class="btn btn-ghost btn-sm mt-12" @click="router.push('/')">← {{ lang === 'cs' ? 'Zpět' : 'Back' }}</button>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import FilterRow from '../components/FilterRow.vue';
import type { FilterRowItem } from '../components/types';
import SbIcon from '../components/SbIcon.vue';
import { SongBattleApi, type GameFilterOptions } from '../api/client';
import type { Strings, Lang } from '../composables/i18n';
import type { GameSession } from '../composables/useGameSession';

const props = defineProps<{ t: Strings; lang: Lang; session: GameSession }>();

const router = useRouter();

const mode = ref<'solo' | 'robin' | 'all'>('all');
const decades = ref<number[]>([]);
const genres = ref<number[]>([]);
const areas = ref<string[]>([]);
const name = ref('');

const options = ref<GameFilterOptions | null>(null);
const optionsLoading = ref(false);
const creating = ref(false);

const modeCards = computed(() => [
    { id: 'solo' as const, title: props.t.mode_solo_t, desc: props.t.mode_solo_d, icon: 'User' },
    { id: 'robin' as const, title: props.t.mode_robin_t, desc: props.t.mode_robin_d, icon: 'Users' },
    { id: 'all' as const, title: props.t.mode_all_t, desc: props.t.mode_all_d, icon: 'Crown' },
]);

const decadeItems = computed<FilterRowItem[]>(() =>
    (options.value?.decades ?? []).map((decade) => ({ value: decade, label: `${decade}s` })),
);

const genreItems = computed<FilterRowItem[]>(() =>
    Object.entries(options.value?.genres ?? {}).map(([id, label]) => ({ value: Number(id), label })),
);

const areaItems = computed<FilterRowItem[]>(() =>
    Object.entries(options.value?.areas ?? {}).map(([code, label]) => ({ value: code, label })),
);

const poolCount = computed(() => options.value?.poolCount ?? 0);

const canCreate = computed(() => name.value.trim() !== '');

function toggleIn<T>(list: T[], value: T): T[] {
    return list.includes(value) ? list.filter((item) => item !== value) : [...list, value];
}

function toggleDecade(value: string | number): void {
    decades.value = toggleIn(decades.value, Number(value));
}

function toggleGenre(value: string | number): void {
    genres.value = toggleIn(genres.value, Number(value));
}

function toggleArea(value: string | number): void {
    areas.value = toggleIn(areas.value, String(value));
}

async function loadOptions(): Promise<void> {
    optionsLoading.value = true;

    try
    {
        options.value = await SongBattleApi.getFilterOptions({
            years: decades.value,
            genres: genres.value,
            areas: areas.value,
        });
    }
    finally
    {
        optionsLoading.value = false;
    }
}

watch([decades, genres, areas], () => {
    loadOptions().catch(() => undefined);
});

onMounted(() => {
    loadOptions().catch(() => undefined);
});

async function create(): Promise<void> {
    if (!canCreate.value || creating.value)
    {
        return;
    }

    creating.value = true;

    try
    {
        await props.session.create({
            years: decades.value,
            genres: genres.value,
            areas: areas.value,
        }, mode.value, name.value.trim());

        if (mode.value === 'solo')
        {
            await props.session.startGame();
        }

        await router.push({ name: 'game', params: { hash: props.session.game.value!.hash } });
    }
    catch (error)
    {
        creating.value = false;

        throw error;
    }
}
</script>

<style lang="scss" scoped>
.sb-spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 2px solid color-mix(in oklab, var(--neon-1) 40%, transparent);
    border-top-color: var(--neon-1);
    border-radius: 50%;
    animation: sb-create-spin 0.7s linear infinite;
}

.sb-spinner--btn {
    width: 14px;
    height: 14px;
    border-color: color-mix(in oklab, currentColor 40%, transparent);
    border-top-color: currentColor;
}

@keyframes sb-create-spin {
    to { transform: rotate(360deg); }
}
</style>

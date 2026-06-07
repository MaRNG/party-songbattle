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
                <div style="font-size: 28px; font-weight: 700; line-height: 1; margin-top: 4px; color: var(--neon-1);">
                    {{ poolCount }}
                </div>
            </div>
            <input v-model="name" class="input" :placeholder="lang === 'cs' ? 'Tvoje jméno' : 'Your name'" style="max-width: 220px;" />
            <button class="btn btn-primary" :disabled="!canCreate" @click="create">{{ t.create_cta }} →</button>
        </div>

        <button class="btn btn-ghost btn-sm mt-12" @click="$emit('back')">← {{ lang === 'cs' ? 'Zpět' : 'Back' }}</button>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import FilterRow from '../components/FilterRow.vue';
import type { FilterRowItem } from '../components/types';
import SbIcon from '../components/SbIcon.vue';
import { SongBattleApi, type GameFilterOptions } from '../api/client';
import type { Strings, Lang } from '../composables/i18n';

const props = defineProps<{ t: Strings; lang: Lang }>();

const emit = defineEmits<{
    (e: 'back'): void;
    (e: 'created', filters: { years?: number[]; genres?: number[]; areas?: string[]; artists?: number[] }, mode: string, name: string): void;
}>();

const mode = ref<'solo' | 'robin' | 'all'>('all');
const decades = ref<number[]>([]);
const genres = ref<number[]>([]);
const areas = ref<string[]>([]);
const name = ref('');

const options = ref<GameFilterOptions | null>(null);

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
    options.value = await SongBattleApi.getFilterOptions({
        years: decades.value,
        genres: genres.value,
        areas: areas.value,
    });
}

watch([decades, genres, areas], () => {
    loadOptions().catch(() => undefined);
});

onMounted(() => {
    loadOptions().catch(() => undefined);
});

function create(): void {
    if (!canCreate.value)
    {
        return;
    }

    emit('created', {
        years: decades.value,
        genres: genres.value,
        areas: areas.value,
    }, mode.value, name.value.trim());
}
</script>

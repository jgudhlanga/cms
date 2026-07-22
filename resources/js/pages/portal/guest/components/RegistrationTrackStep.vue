<script setup lang="ts">
import { computed, ref, watch } from 'vue';

export type TrackOption = {
    value: string;
    label: string;
    description: string;
};

export type ContinuousFocus = 'sdp' | 'ojet';

type VisibleOption =
    | { kind: 'track'; value: string; label: string; description: string }
    | { kind: 'focus'; focus: ContinuousFocus; labelKey: string };

const props = withDefaults(
    defineProps<{
        tracks: TrackOption[];
        continuousHasSdp?: boolean;
        continuousHasOjet?: boolean;
        selectedTrack?: string | null;
        continuousFocus?: ContinuousFocus | null;
    }>(),
    {
        continuousHasSdp: true,
        continuousHasOjet: true,
        selectedTrack: null,
        continuousFocus: null,
    },
);

const emit = defineEmits<{
    'update:selectedTrack': [value: string | null];
    'update:continuousFocus': [value: ContinuousFocus | null];
}>();

const selectedTrack = ref<string | null>(props.selectedTrack);
const continuousFocus = ref<ContinuousFocus | null>(props.continuousFocus);

watch(
    () => props.selectedTrack,
    (value) => {
        selectedTrack.value = value ?? null;
    },
);

watch(
    () => props.continuousFocus,
    (value) => {
        continuousFocus.value = value ?? null;
    },
);

const hasContinuousTrack = computed(() => props.tracks.some((track) => track.value === 'continuous'));

const visibleOptions = computed<VisibleOption[]>(() => {
    const options: VisibleOption[] = props.tracks
        .filter((track) => track.value !== 'continuous')
        .map((track) => ({
            kind: 'track' as const,
            value: track.value,
            label: track.label,
            description: track.description,
        }));

    if (hasContinuousTrack.value && props.continuousHasSdp) {
        options.push({
            kind: 'focus',
            focus: 'sdp',
            labelKey: 'trans.application_continuous_focus_sdp',
        });
    }

    if (hasContinuousTrack.value && props.continuousHasOjet) {
        options.push({
            kind: 'focus',
            focus: 'ojet',
            labelKey: 'trans.application_continuous_focus_ojet',
        });
    }

    return options;
});

const isSelected = (option: VisibleOption): boolean => {
    if (option.kind === 'focus') {
        return selectedTrack.value === 'continuous' && continuousFocus.value === option.focus;
    }

    return selectedTrack.value === option.value && continuousFocus.value === null;
};

const selectOption = (option: VisibleOption) => {
    if (option.kind === 'focus') {
        selectedTrack.value = 'continuous';
        continuousFocus.value = option.focus;
        emit('update:selectedTrack', 'continuous');
        emit('update:continuousFocus', option.focus);
        return;
    }

    selectedTrack.value = option.value;
    continuousFocus.value = null;
    emit('update:selectedTrack', option.value);
    emit('update:continuousFocus', null);
};

const optionKey = (option: VisibleOption): string =>
    option.kind === 'focus' ? `focus-${option.focus}` : `track-${option.value}`;
</script>

<template>
    <div
        role="radiogroup"
        :aria-label="$t('trans.application_track_choose_title')"
        class="flex flex-col gap-3"
    >
        <button
            v-for="option in visibleOptions"
            :key="optionKey(option)"
            type="button"
            role="radio"
            :aria-checked="isSelected(option)"
            class="rounded-2xl border p-5 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            :class="
                isSelected(option)
                    ? 'border-primary bg-primary/5 shadow-sm'
                    : 'border-border bg-card hover:border-primary/40'
            "
            @click="selectOption(option)"
        >
            <div class="font-semibold text-foreground">
                <template v-if="option.kind === 'focus'">
                    {{ $t(option.labelKey) }}
                </template>
                <template v-else>
                    {{ option.label }}
                </template>
            </div>
            <p
                v-if="option.kind === 'track'"
                class="mt-1 text-sm text-muted-foreground"
            >
                {{ option.description }}
            </p>
        </button>
    </div>
</template>

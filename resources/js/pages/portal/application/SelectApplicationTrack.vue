<script setup lang="ts">
import PortalApplicationShell from '@/components/portal/PortalApplicationShell.vue';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { ColorVariant } from '@/enums/colors';
import { BaseButton } from '@/components/core/button';
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type TrackOption = {
    value: string;
    label: string;
    description: string;
};

interface Props {
    tracks: TrackOption[];
    currentTrack?: string | null;
    applicationStep?: string;
}

const props = defineProps<Props>();
const selected = ref(props.currentTrack ?? props.tracks[0]?.value ?? null);
const submitting = ref(false);
const { redirectIfClosed } = useRegistrationAvailability();

onMounted(() => {
    redirectIfClosed();
});

const selectTrack = (value: string) => {
    selected.value = value;
};

const continueWithTrack = () => {
    if (!selected.value) {
        return;
    }

    submitting.value = true;
    router.post(
        route('portal.application.select-track'),
        { track: selected.value },
        {
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
};
</script>

<template>
    <PortalApplicationShell>
        <div class="mx-auto flex w-full max-w-2xl flex-col px-5 pb-12">
            <div class="mb-8 text-center">
                <h1 class="text-xl font-semibold text-foreground">
                    {{ $t('trans.application_track_choose_title') }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ $t('trans.application_track_choose_description') }}
                </p>
            </div>

            <div
                role="radiogroup"
                :aria-label="$t('trans.application_track_choose_title')"
                class="flex flex-col gap-3"
            >
                <button
                    v-for="track in tracks"
                    :key="track.value"
                    type="button"
                    role="radio"
                    :aria-checked="selected === track.value"
                    class="rounded-2xl border p-5 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :class="
                        selected === track.value
                            ? 'border-primary bg-primary/5 shadow-sm'
                            : 'border-border bg-card hover:border-primary/40'
                    "
                    @click="selectTrack(track.value)"
                >
                    <div class="font-semibold text-foreground">{{ track.label }}</div>
                    <p class="mt-1 text-sm text-muted-foreground">{{ track.description }}</p>
                </button>
            </div>

            <div class="mt-8 flex justify-end">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.primary"
                    :disabled="!selected || submitting"
                    @click="continueWithTrack"
                >
                    {{ $t('trans.continue') }}
                </BaseButton>
            </div>
        </div>
    </PortalApplicationShell>
</template>

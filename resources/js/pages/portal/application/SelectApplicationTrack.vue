<script setup lang="ts">
import PortalApplicationShell from '@/components/portal/PortalApplicationShell.vue';
import { useRegistrationAvailability } from '@/composables/students/useRegistrationAvailability';
import { ColorVariant } from '@/enums/colors';
import { BaseButton } from '@/components/core/button';
import RegistrationTrackStep, {
    type ContinuousFocus,
    type TrackOption,
} from '@/pages/portal/guest/components/RegistrationTrackStep.vue';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface Props {
    tracks: TrackOption[];
    currentTrack?: string | null;
    currentContinuousFocus?: ContinuousFocus | null;
    continuousHasSdp?: boolean;
    continuousHasOjet?: boolean;
    applicationStep?: string;
}

const props = withDefaults(defineProps<Props>(), {
    continuousHasSdp: true,
    continuousHasOjet: true,
    currentContinuousFocus: null,
});

const selectedTrack = ref<string | null>(props.currentTrack ?? props.tracks[0]?.value ?? null);
const continuousFocus = ref<ContinuousFocus | null>(
    props.currentTrack === 'continuous'
        ? (props.currentContinuousFocus ?? (props.continuousHasSdp ? 'sdp' : props.continuousHasOjet ? 'ojet' : null))
        : null,
);
const submitting = ref(false);
const { redirectIfClosed } = useRegistrationAvailability();

onMounted(() => {
    redirectIfClosed();

    if (selectedTrack.value === 'continuous' && continuousFocus.value === null) {
        if (props.continuousHasSdp) {
            continuousFocus.value = 'sdp';
        } else if (props.continuousHasOjet) {
            continuousFocus.value = 'ojet';
        }
    }
});

const canContinue = computed(() => {
    if (!selectedTrack.value) {
        return false;
    }

    if (selectedTrack.value === 'continuous') {
        return continuousFocus.value === 'sdp' || continuousFocus.value === 'ojet';
    }

    return true;
});

const continueWithTrack = () => {
    if (!canContinue.value || !selectedTrack.value) {
        return;
    }

    submitting.value = true;
    router.post(
        route('portal.application.select-track'),
        {
            track: selectedTrack.value,
            continuous_focus: selectedTrack.value === 'continuous' ? continuousFocus.value : null,
        },
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

            <RegistrationTrackStep
                v-model:selected-track="selectedTrack"
                v-model:continuous-focus="continuousFocus"
                :tracks="tracks"
                :continuous-has-sdp="continuousHasSdp"
                :continuous-has-ojet="continuousHasOjet"
            />

            <div class="mt-8 flex justify-end">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.primary"
                    :disabled="!canContinue || submitting"
                    @click="continueWithTrack"
                >
                    {{ $t('trans.continue') }}
                </BaseButton>
            </div>
        </div>
    </PortalApplicationShell>
</template>

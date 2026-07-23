<script setup lang="ts">
import RegistrationIntentSummary from '@/components/portal/RegistrationIntentSummary.vue';
import RegistrationStepper from '@/components/portal/RegistrationStepper.vue';
import { BaseButton } from '@/components/core/button';
import { ColorVariant } from '@/enums/colors';
import RegistrationBrandHeader from '@/pages/portal/guest/components/RegistrationBrandHeader.vue';
import RegistrationGuide from '@/pages/portal/guest/RegistrationGuide.vue';
import RegistrationTrackStep, {
    type ContinuousFocus,
    type TrackOption,
} from '@/pages/portal/guest/components/RegistrationTrackStep.vue';
import { useRegistrationStepNavigation } from '@/composables/students/useRegistrationStepNavigation';
import type { StepperVariant } from '@/components/portal/RegistrationStepper.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

type IntentSummary = {
    track?: string | null;
    trackLabel?: string | null;
    continuousFocus?: string | null;
    levelName?: string | null;
    intakeName?: string | null;
    requiresFee?: boolean;
    stepperVariant?: string;
};

const props = withDefaults(
    defineProps<{
        tracks: TrackOption[];
        currentTrack?: string | null;
        currentContinuousFocus?: ContinuousFocus | null;
        continuousHasSdp?: boolean;
        continuousHasOjet?: boolean;
        intentSummary?: IntentSummary | null;
        stepperVariant?: StepperVariant;
        requiresFee?: boolean;
        applicationStep?: string;
    }>(),
    {
        continuousHasSdp: true,
        continuousHasOjet: true,
        currentContinuousFocus: null,
        intentSummary: null,
        stepperVariant: 'regular',
        requiresFee: false,
    },
);

const { navigateToRegistrationStep } = useRegistrationStepNavigation();

const selectedTrack = ref<string | null>(props.currentTrack ?? props.tracks[0]?.value ?? null);
const continuousFocus = ref<ContinuousFocus | null>(
    props.currentTrack === 'continuous'
        ? (props.currentContinuousFocus ?? (props.continuousHasSdp ? 'sdp' : props.continuousHasOjet ? 'ojet' : null))
        : null,
);
const submitting = ref(false);

onMounted(() => {
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
        route('portal.register.select-track'),
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
    <Head :title="$t('trans.application_track_choose_title')" />
    <div class="min-h-svh bg-background">
        <div class="flex min-h-svh flex-col lg:flex-row">
            <div class="flex w-full flex-1 flex-col p-4 pt-2 sm:p-6 md:pt-6 lg:w-[62%] lg:min-w-0 lg:p-10">
                <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
                    <RegistrationBrandHeader />
                    <RegistrationStepper
                        active-path="zimbabwean"
                        highlighted-step="choose-track"
                        :stepper-variant="stepperVariant"
                        :requires-fee="requiresFee"
                        @navigate="navigateToRegistrationStep"
                    />
                    <RegistrationIntentSummary :summary="intentSummary" />

                    <div class="rounded-2xl border border-border bg-card p-5 text-card-foreground shadow-md sm:p-8">
                        <div class="mb-5 text-center">
                            <h1 class="text-lg font-semibold text-foreground">
                                {{ $t('trans.application_track_choose_title') }}
                            </h1>
                            <p class="mt-1.5 text-sm text-muted-foreground">
                                {{ $t('trans.application_track_choose_description') }}
                            </p>
                            <p class="mt-2 text-xs leading-snug text-muted-foreground">
                                {{ $t('trans.registration_eligibility_help') }}
                            </p>
                        </div>

                        <RegistrationTrackStep
                            v-model:selected-track="selectedTrack"
                            v-model:continuous-focus="continuousFocus"
                            :tracks="tracks"
                            :continuous-has-sdp="continuousHasSdp"
                            :continuous-has-ojet="continuousHasOjet"
                        />

                        <div class="mt-6 flex justify-end">
                            <BaseButton
                                type="button"
                                :variant="ColorVariant.primary"
                                :disabled="!canContinue || submitting"
                                classes="min-h-10 rounded-xl"
                                @click="continueWithTrack"
                            >
                                {{ $t('trans.continue') }}
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </div>
            <RegistrationGuide
                active-path="zimbabwean"
                highlighted-step="choose-track"
                :stepper-variant="stepperVariant"
                :requires-fee="requiresFee"
            />
        </div>
    </div>
</template>

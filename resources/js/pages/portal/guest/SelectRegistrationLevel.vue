<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import RegistrationIntentSummary from '@/components/portal/RegistrationIntentSummary.vue';
import RegistrationStepper from '@/components/portal/RegistrationStepper.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import { usePortalLevelSelection } from '@/composables/students/usePortalLevelSelection';
import { useRegistrationStepNavigation } from '@/composables/students/useRegistrationStepNavigation';
import type { StepperVariant } from '@/components/portal/RegistrationStepper.vue';
import { TypeVariant } from '@/enums/type-variants';
import RegistrationBrandHeader from '@/pages/portal/guest/components/RegistrationBrandHeader.vue';
import RegistrationGuide from '@/pages/portal/guest/RegistrationGuide.vue';
import { IntakePeriod, Level } from '@/types/institution';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type AvailabilityIssue = 'no_open_levels' | 'no_active_intakes' | null;

type IntentSummary = {
    track?: string | null;
    trackLabel?: string | null;
    continuousFocus?: string | null;
    levelName?: string | null;
    intakeName?: string | null;
};

interface Props {
    levels: Level[] | { data: Level[] };
    intakePeriods?: IntakePeriod[];
    requiresIntakeSelection?: boolean;
    openLevelCount?: number;
    hasActiveIntakes?: boolean;
    availabilityIssue?: AvailabilityIssue;
    selectLevelRoute?: string;
    applicationTrack?: string | null;
    applicationTrackLabel?: string | null;
    continuousFocus?: string | null;
    intentSummary?: IntentSummary | null;
    stepperVariant?: StepperVariant;
    requiresFee?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    requiresIntakeSelection: false,
    intakePeriods: () => [],
    hasActiveIntakes: true,
    availabilityIssue: null,
    selectLevelRoute: 'portal.register.select-level',
    applicationTrack: null,
    applicationTrackLabel: null,
    intentSummary: null,
    stepperVariant: 'regular',
    requiresFee: false,
});

const { navigateToRegistrationStep } = useRegistrationStepNavigation();

const levelList = computed(() => {
    if (Array.isArray(props.levels)) {
        return props.levels;
    }

    return Array.isArray(props.levels?.data) ? props.levels.data : [];
});

const hasLevels = computed(() => levelList.value.length > 0);
const showNoLevelsAlert = computed(() => props.availabilityIssue === 'no_open_levels' || !hasLevels.value);
const showNoIntakesAlert = computed(() => props.availabilityIssue === 'no_active_intakes');
const canSelectLevel = computed(() => hasLevels.value && props.hasActiveIntakes);

const selectedIntakePeriodId = ref<number | null>(
    props.intakePeriods.length === 1 ? Number(props.intakePeriods[0].id) : null,
);

const { selectLevel } = usePortalLevelSelection(props.selectLevelRoute);

const onApply = (levelId: string) => {
    selectLevel(levelId, selectedIntakePeriodId.value, props.requiresIntakeSelection);
};
</script>

<template>
    <Head :title="$t('trans.portal_application_step_level')" />
    <div class="min-h-svh bg-background">
        <div class="flex min-h-svh flex-col lg:flex-row">
            <div class="flex w-full flex-1 flex-col p-4 pt-2 sm:p-6 md:pt-6 lg:w-[62%] lg:min-w-0 lg:p-10">
                <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col">
                    <RegistrationBrandHeader />
                    <RegistrationStepper
                        active-path="zimbabwean"
                        highlighted-step="choose-level"
                        :stepper-variant="stepperVariant"
                        :requires-fee="requiresFee"
                        @navigate="navigateToRegistrationStep"
                    />
                    <RegistrationIntentSummary :summary="intentSummary" />

                    <div class="rounded-2xl border border-border bg-card p-5 text-card-foreground shadow-md sm:p-8">
                        <div class="mb-5 text-center">
                            <h1 class="text-lg font-semibold text-foreground">
                                {{ $t('trans.portal_application_step_level') }}
                            </h1>
                            <p v-if="applicationTrackLabel" class="mt-1 text-sm font-medium text-primary">
                                {{ applicationTrackLabel }}
                                <template v-if="continuousFocus"> · {{ continuousFocus.toUpperCase() }}</template>
                            </p>
                            <p v-if="showNoLevelsAlert" class="mt-2 text-sm text-muted-foreground">
                                {{ $t('trans.portal_no_levels_available_description') }}
                            </p>
                            <p v-else-if="showNoIntakesAlert" class="mt-2 text-sm text-muted-foreground">
                                {{ $t('trans.portal_no_active_intakes_description') }}
                            </p>
                        </div>

                        <div v-if="requiresIntakeSelection && intakePeriods.length > 1" class="mb-6 w-full">
                            <IntakePeriodComboSelect
                                v-model="selectedIntakePeriodId"
                                :data="intakePeriods"
                                :is-required="true"
                            />
                        </div>

                        <BaseAlert
                            v-if="showNoLevelsAlert"
                            class="mb-4"
                            :title="$t('trans.portal_no_levels_available')"
                            :description="$t('trans.portal_no_levels_available_description')"
                            :type="TypeVariant.warning"
                        />

                        <BaseAlert
                            v-if="showNoIntakesAlert"
                            class="mb-4"
                            :title="$t('trans.portal_no_active_intakes')"
                            :description="$t('trans.portal_no_active_intakes_description')"
                            :type="TypeVariant.warning"
                        />

                        <div v-if="canSelectLevel" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div
                                v-for="(level, index) in levelList"
                                :key="level.id"
                                class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
                            >
                                <div class="p-4">
                                    <div class="mb-1 flex items-center justify-between gap-2">
                                        <h3 class="text-base font-semibold text-foreground">
                                            {{ level.attributes.name }}
                                        </h3>
                                        <span class="text-xs text-muted-foreground">#{{ Number(index) + 1 }}</span>
                                    </div>
                                    <p class="text-xs text-muted-foreground">{{ level.attributes.description }}</p>
                                </div>
                                <div class="border-t border-border bg-muted/40 px-4 py-3">
                                    <button
                                        type="button"
                                        class="inline-flex w-full items-center justify-center rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="!level.attributes.showOnCurrentApplicationPeriod"
                                        @click="() => onApply(String(level.id))"
                                    >
                                        {{ $t('general.apply_now') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <RegistrationGuide
                active-path="zimbabwean"
                highlighted-step="choose-level"
                :stepper-variant="stepperVariant"
                :requires-fee="requiresFee"
            />
        </div>
    </div>
</template>

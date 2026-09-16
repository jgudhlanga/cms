<script setup lang="ts">
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import BaseAccordionItem from '@/components/core/accordion/BaseAccordionItem.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseTag from '@/components/core/util/BaseTag.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { IntakePeriod } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

export interface ApplicationHubProps {
    openIntakes: IntakePeriod[] | { data: IntakePeriod[] };
    canStartApplication: boolean;
    hasPaidApplicationFee: boolean;
    paidLevelId?: number | null;
    paidLevelName?: string | null;
    hasReapplyAcknowledgement: boolean;
    canContinueInClass: boolean;
    continueInClassUrl: string;
    canApplyToNextLevel?: boolean;
    nextLevelId?: number | null;
    nextLevelName?: string | null;
    nextDepartmentLevelId?: number | null;
    institutionDepartmentId?: number | null;
    canApplyToNextStage?: boolean;
    nextStageId?: number | null;
    nextStageName?: string | null;
    requiresIntakeSelection: boolean;
}

interface Props {
    applicationHub: ApplicationHubProps;
    existingApplicationIntakeIds?: Array<string | number>;
    highlightFeePaid?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    existingApplicationIntakeIds: () => [],
    highlightFeePaid: false,
});

const { navigateTo } = useUtils();

const intakeList = computed(() => {
    const raw = props.applicationHub.openIntakes;
    if (Array.isArray(raw)) {
        return raw;
    }

    return Array.isArray(raw?.data) ? raw.data : [];
});

const intakesWithoutApplication = computed(() => {
    const existingIds = new Set(props.existingApplicationIntakeIds.map((id) => String(id)));

    return intakeList.value.filter((intake) => !existingIds.has(String(intake.id)));
});

const isNextStageApply = computed(() => props.applicationHub.canApplyToNextStage === true);
const isNextLevelApply = computed(() => props.applicationHub.canApplyToNextLevel === true && !isNextStageApply.value);
const isLockedProgrammeApply = computed(() => isNextStageApply.value || isNextLevelApply.value);

const hubIntakes = computed(() =>
    isNextStageApply.value ? intakeList.value : intakesWithoutApplication.value,
);

const showHub = computed(
    () => props.applicationHub.canStartApplication && hubIntakes.value.length > 0,
);

const selectedIntakeId = ref<number | null>(
    hubIntakes.value.length === 1 ? Number(hubIntakes.value[0].id) : null,
);

const feePaidHighlight = ref(props.highlightFeePaid);

const acknowledgeForm = useForm({
    intake_period_id: null as number | null,
    acknowledged: false as boolean,
});

const submitAcknowledge = () => {
    const intakeId = selectedIntakeId.value ?? Number(hubIntakes.value[0]?.id ?? 0);
    acknowledgeForm.intake_period_id = intakeId;
    acknowledgeForm.post(route('portal.profile.applications.acknowledge'), {
        preserveScroll: true,
    });
};

const goToLevelSelection = () => {
    navigateTo(route('portal.profile.applications.level'));
};

const continueApplication = () => {
    navigateTo(route('portal.application.returning'));
};

const defaultOpenIds = computed(() => hubIntakes.value.map((intake) => String(intake.id)));

const hubStep = computed(() => {
    if (props.applicationHub.hasPaidApplicationFee && props.applicationHub.hasReapplyAcknowledgement) {
        return 'continue';
    }

    if (props.applicationHub.hasReapplyAcknowledgement) {
        return 'level';
    }

    return 'acknowledge';
});

const nextLevelName = computed(() => props.applicationHub.nextLevelName ?? '');
const nextStageName = computed(() => props.applicationHub.nextStageName ?? '');
const lockedTargetName = computed(() =>
    isNextStageApply.value ? nextStageName.value : nextLevelName.value,
);
</script>

<template>
    <div v-if="showHub" class="mb-6">
        <BaseAccordion class="w-full" :default-value="defaultOpenIds">
            <BaseAccordionItem
                v-for="intake in hubIntakes"
                :key="intake.id"
                :value="String(intake.id)"
                :title="intake.attributes?.name ?? ''"
                :description="
                    isNextStageApply
                        ? $t('trans.returning_student_hub_next_stage_intake_description', { stage: nextStageName })
                        : isNextLevelApply
                          ? $t('trans.returning_student_hub_next_level_intake_description', { level: nextLevelName })
                          : $t('trans.returning_student_hub_intake_description')
                "
            >
                <template #trigger-extra>
                    <BaseTag
                        :title="
                            isNextStageApply
                                ? $t('trans.returning_student_hub_next_stage_tag')
                                : isNextLevelApply
                                  ? $t('trans.returning_student_hub_next_level_tag')
                                  : $t('students.current_intake')
                        "
                        :variant="ColorVariant.success"
                        classes="cursor-default"
                    />
                </template>

                <div class="space-y-4 p-1">
                    <BaseAlert
                        v-if="isNextStageApply"
                        :type="TypeVariant.info"
                        :description="
                            $t('trans.returning_student_hub_next_stage_banner', {
                                stage: nextStageName,
                            })
                        "
                    />
                    <BaseAlert
                        v-else-if="isNextLevelApply"
                        :type="TypeVariant.info"
                        :description="
                            $t('trans.returning_student_hub_next_level_banner', {
                                level: nextLevelName,
                            })
                        "
                    />

                    <BaseAlert
                        v-if="feePaidHighlight && hubStep === 'continue'"
                        :type="TypeVariant.success"
                        :description="$t('trans.returning_student_hub_fee_paid')"
                    />

                    <template v-if="hubStep === 'acknowledge'">
                        <p class="text-sm text-muted-foreground">
                            {{
                                isNextStageApply
                                    ? $t('trans.returning_student_hub_next_stage_onboarding', { stage: nextStageName })
                                    : isNextLevelApply
                                      ? $t('trans.returning_student_hub_next_level_onboarding', { level: nextLevelName })
                                      : $t('trans.returning_student_onboarding_description')
                            }}
                        </p>
                        <div
                            v-if="applicationHub.requiresIntakeSelection && hubIntakes.length > 1"
                            class="max-w-md"
                        >
                            <IntakePeriodComboSelect
                                v-model="selectedIntakeId"
                                :data="hubIntakes"
                                :is-required="true"
                            />
                        </div>
                        <label class="flex items-start gap-2 text-sm">
                            <input v-model="acknowledgeForm.acknowledged" type="checkbox" class="mt-1" />
                            <span>{{ $t('trans.returning_student_acknowledge_label') }}</span>
                        </label>
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary"
                            :disabled="!acknowledgeForm.acknowledged || (applicationHub.requiresIntakeSelection && !selectedIntakeId)"
                            :processing="acknowledgeForm.processing"
                            :title="
                                isNextStageApply
                                    ? $t('trans.returning_student_hub_next_stage_start')
                                    : isNextLevelApply
                                      ? $t('trans.returning_student_hub_next_level_start')
                                      : $t('trans.returning_student_hub_start')
                            "
                            @click="submitAcknowledge"
                        />
                    </template>

                    <template v-else-if="hubStep === 'level'">
                        <p class="text-sm text-muted-foreground">
                            {{
                                isNextStageApply
                                    ? $t('trans.returning_student_hub_next_stage_select', { stage: nextStageName })
                                    : isNextLevelApply
                                      ? $t('trans.returning_student_hub_next_level_select', { level: nextLevelName })
                                      : $t('trans.returning_student_hub_select_level')
                            }}
                        </p>
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary"
                            :title="
                                isNextStageApply
                                    ? $t('trans.returning_student_hub_next_stage_go_select', { stage: nextStageName })
                                    : isNextLevelApply
                                      ? $t('trans.returning_student_hub_next_level_go_select', { level: nextLevelName })
                                      : $t('trans.returning_student_hub_go_select_level')
                            "
                            @click="goToLevelSelection"
                        />
                    </template>

                    <template v-else>
                        <p class="text-sm text-muted-foreground">
                            {{
                                isLockedProgrammeApply
                                    ? $t('trans.returning_student_hub_next_stage_continue_banner', {
                                          intake: intake.attributes?.name ?? '',
                                          stage: lockedTargetName || (applicationHub.paidLevelName ?? ''),
                                      })
                                    : $t('trans.returning_student_reapply_banner', {
                                          intake: intake.attributes?.name ?? '',
                                      })
                            }}
                        </p>
                        <p v-if="applicationHub.paidLevelName || lockedTargetName" class="text-sm text-foreground">
                            {{ $t('trans.returning_student_hub_level') }}:
                            {{ lockedTargetName || applicationHub.paidLevelName }}
                        </p>
                        <BaseButton
                            type="button"
                            :variant="ColorVariant.primary"
                            :class="{ 'ring-2 ring-primary ring-offset-2': feePaidHighlight }"
                            :title="
                                isNextStageApply
                                    ? $t('trans.returning_student_hub_next_stage_continue')
                                    : isNextLevelApply
                                      ? $t('trans.returning_student_hub_next_level_continue')
                                      : $t('trans.returning_student_hub_continue')
                            "
                            @click="continueApplication"
                        />
                    </template>
                </div>
            </BaseAccordionItem>
        </BaseAccordion>
    </div>
</template>

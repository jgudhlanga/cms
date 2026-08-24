<script setup lang="ts">
import LabelValue from '@/components/core/util/LabelValue.vue';
import EnrolmentIdNumberField from '@/pages/enrolments/partials/shared/EnrolmentIdNumberField.vue';
import { useUtils } from '@/composables/core/useUtils';
import { Enrolment } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    application: Enrolment;
    oLevelRequired: boolean;
    previousLevelRequired: boolean;
    readWriteRequired: boolean;
    requiredLevel: string;
    compact?: boolean;
    embedded?: boolean;
    highlightUnderReview?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
    embedded: false,
    highlightUnderReview: false,
});

const { isItTrue, yesOrNo, isNativeCitizen } = useUtils();

const disabilityStatus = computed(() => {
    if (props.application?.attributes?.disabilityStatus === 'prefer_not_to_say') {
        return 'Prefer not to say';
    }

    return yesOrNo(isItTrue(props.application?.attributes?.disabilityStatus));
});

const isNative = computed(() => isNativeCitizen(props.application?.attributes?.idType ?? ''));
</script>

<template>
    <template v-if="embedded">
        <div class="flex flex-col gap-4">
            <h3 class="text-[0.65rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                {{ $t('enrolments.submitted_by_applicant') }}
            </h3>
            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <LabelValue
                    :label="$tChoice('trans.name', 1)"
                    :value="application?.attributes?.studentName"
                    :highlighted="highlightUnderReview"
                />
                <LabelValue :label="$tChoice('trans.id_type', 1)" :value="application?.attributes?.idType ?? '---'" />
                <EnrolmentIdNumberField
                    v-if="isNative"
                    :application="application"
                    display-only
                    :highlighted="highlightUnderReview"
                />
                <template v-else>
                    <LabelValue
                        :label="$tChoice('trans.passport_number', 1)"
                        :value="application?.attributes?.passportNumber ?? '---'"
                        :highlighted="highlightUnderReview"
                    />
                    <LabelValue :label="$t('trans.ui_country_of_issue')" :value="application?.attributes?.country ?? '---'" />
                </template>
                <LabelValue
                    :label="$t('trans.disability')"
                    :value="disabilityStatus"
                    :highlighted="highlightUnderReview"
                />
                <LabelValue :label="$t('trans.phone_number')" :value="application?.attributes?.phoneNumber ?? ''" />
                <LabelValue :label="$t('trans.email_address')" :value="application?.attributes?.email ?? ''" />
                <LabelValue
                    :label="$tChoice('trans.student_number', 1)"
                    :value="application?.attributes?.studentNumber ?? $t('enrolments.not_yet_assigned')"
                    :value-classes="!application?.attributes?.studentNumber ? 'text-muted-foreground font-normal' : undefined"
                />
                <LabelValue
                    :label="$tChoice('trans.tracking_number', 1)"
                    :value="application?.attributes?.applicationTrackingNumber ?? ''"
                />
            </div>

            <div v-if="oLevelRequired" class="flex flex-col gap-2">
                <h4 class="text-[0.65rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                    {{ $t('trans.o_level_subjects') }}
                </h4>
                <div class="grid max-h-40 grid-cols-2 gap-x-4 gap-y-1 overflow-y-auto text-sm">
                    <LabelValue
                        v-for="result in application?.relationships?.oLevelResults ?? []"
                        :key="result.id"
                        :label="result.attributes.subject"
                        :value="`${result.attributes.grade} (${result.attributes.examYear})`"
                    />
                </div>
            </div>

            <div v-if="previousLevelRequired" class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <LabelValue
                    :label="`Completed ${requiredLevel ?? ''}`"
                    :value="yesOrNo(isItTrue(application?.attributes?.requiredLevelCompleted))"
                />
            </div>

            <div v-if="readWriteRequired" class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <LabelValue
                    :label="$t('trans.ui_read_and_write_confirmed')"
                    :value="yesOrNo(isItTrue(application?.attributes?.readWriteAcknowledged))"
                />
            </div>
        </div>
    </template>

    <template v-else>
    <BaseCard
        :title="compact ? $t('enrolments.submitted_by_applicant') : $t('trans.personal')"
        :description="compact ? undefined : $t('trans.ui_personal_details_of_the_applicant')"
        :class="compact ? 'shadow-xs' : ''"
    >
        <div :class="compact ? 'grid grid-cols-2 gap-x-4 gap-y-2 text-sm' : 'grid grid-cols-2 gap-3'">
            <LabelValue :label="$tChoice('trans.name', 1)" :value="application?.attributes?.studentName" />
            <LabelValue :label="$tChoice('trans.id_type', 1)" :value="application?.attributes?.idType ?? '---'" />
            <EnrolmentIdNumberField v-if="isNative" :application="application" />
            <template v-else>
                <LabelValue
                    :label="$tChoice('trans.passport_number', 1)"
                    :value="application?.attributes?.passportNumber ?? '---'"
                />
                <LabelValue :label="$t('trans.ui_country_of_issue')" :value="application?.attributes?.country ?? '---'" />
            </template>
            <LabelValue :label="$t('trans.disability')" :value="disabilityStatus" />
            <LabelValue :label="$t('trans.phone_number')" :value="application?.attributes?.phoneNumber ?? ''" />
            <LabelValue :label="$t('trans.email_address')" :value="application?.attributes?.email ?? ''" />
            <LabelValue
                :label="$tChoice('trans.student_number', 1)"
                :value="application?.attributes?.studentNumber ?? '---'"
            />
            <LabelValue
                :label="$tChoice('trans.tracking_number', 1)"
                :value="application?.attributes?.applicationTrackingNumber ?? ''"
            />
        </div>
    </BaseCard>

    <template v-if="!compact">
        <BaseCard :title="$tChoice('trans.course', 1) + ' details'" :description="$t('trans.ui_course_specific_details')">
            <div class="grid grid-cols-2 gap-3">
                <LabelValue :label="$tChoice('trans.department', 1)" :value="application?.attributes?.department ?? ''" />
                <LabelValue :label="$tChoice('trans.level', 1)" :value="application?.attributes?.level ?? ''" />
                <LabelValue :label="$tChoice('trans.course', 1)" :value="application?.attributes?.course ?? ''" />
                <LabelValue :label="$tChoice('trans.intake_period', 1)" :value="application?.attributes?.intakePeriod ?? ''" />
                <LabelValue :label="$tChoice('trans.mode_of_study', 1)" :value="application?.attributes?.modeOfStudy ?? ''" />
                <LabelValue :label="$tChoice('trans.tracking_number', 1)" :value="application?.attributes?.applicationTrackingNumber ?? ''" />
                <LabelValue
                    :label="$tChoice('trans.student_number', 1)"
                    :value="application?.attributes?.studentNumber ?? '---'"
                />
            </div>
        </BaseCard>
    </template>

    <BaseCard
        v-if="oLevelRequired"
        :title="$t('trans.o_level_subjects')"
        :description="compact ? undefined : $t('trans.ui_provided_o_level_results_2')"
        :class="compact ? 'shadow-xs' : ''"
    >
        <div
            :class="[
                compact ? 'grid max-h-40 grid-cols-2 gap-x-4 gap-y-1 overflow-y-auto text-sm' : 'grid grid-cols-2 gap-3',
            ]"
        >
            <LabelValue
                v-for="result in application?.relationships?.oLevelResults ?? []"
                :key="result.id"
                :label="result.attributes.subject"
                :value="`${result.attributes.grade} (${result.attributes.examYear})`"
            />
        </div>
    </BaseCard>

    <BaseCard
        v-if="previousLevelRequired"
        :title="$t('trans.level_required', { level: requiredLevel ?? '' })"
        :description="compact ? undefined : $t('trans.ui_previous_level_completed_by_the_applicant')"
        :class="compact ? 'shadow-xs' : ''"
    >
        <LabelValue
            :label="`Completed ${requiredLevel ?? ''}`"
            :value="yesOrNo(isItTrue(application?.attributes?.requiredLevelCompleted))"
        />
    </BaseCard>

    <BaseCard
        v-if="readWriteRequired"
        :title="$t('trans.ui_read_and_write_requirement')"
        :description="compact ? undefined : $t('trans.ui_applicant_should_be_able_to_read_and_write')"
        :class="compact ? 'shadow-xs' : ''"
    >
        <LabelValue
            :label="$t('trans.ui_read_and_write_confirmed')"
            :value="yesOrNo(isItTrue(application?.attributes?.readWriteAcknowledged))"
        />
    </BaseCard>
    </template>
</template>

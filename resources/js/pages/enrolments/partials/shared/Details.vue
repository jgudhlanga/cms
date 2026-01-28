<script setup lang="ts">
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useUtils } from '@/composables/core/useUtils';
import { Enrolment } from '@/types/enrolments';
import { computed } from 'vue';

interface Props {
    application: Enrolment;
    oLevelRequired: boolean;
    previousLevelRequired: boolean;
    readWriteRequired: boolean;
    requiredLevel: string;
}

const props = defineProps<Props>();

const { application } = props;

const badgeClass = 'rounded-full px-3 py-.05 bg-green-100 text-green-600 w-fit';
const { isItTrue, yesOrNo, isNativeCitizen } = useUtils();

const disabilityStatus = computed(() => {
    if (application?.attributes?.disabilityStatus === 'prefer_not_to_say') {
        return 'Prefer not to say';
    } else {
        return yesOrNo(isItTrue(application?.attributes?.disabilityStatus));
    }
});
</script>

<template>
    <BaseCard :title="$t('trans.personal')" description="Personal details of the applicant">
        <div class="grid grid-cols-2 gap-3">
            <LabelValue :value-classes="badgeClass" :label="$tChoice('trans.name', 1)" :value="application?.attributes?.studentName" />
            <LabelValue :label="$tChoice('trans.id_type', 1)" :value="application?.attributes?.idType ?? '---'" />
            <template v-if="isNativeCitizen(application?.attributes?.idType ?? '')">
                <LabelValue :value-classes="badgeClass" :label="$tChoice('trans.id_number', 1)" :value="application?.attributes?.idNumber ?? '---'" />
            </template>
            <template>
                <LabelValue
                    :value-classes="badgeClass"
                    :label="$tChoice('trans.passport_number', 1)"
                    :value="application?.attributes?.passportNumber ?? '---'"
                />
                <LabelValue :value-classes="badgeClass" label="Country of issue" :value="application?.attributes?.country ?? '---'" />
            </template>
            <LabelValue :value-classes="badgeClass" :label="$t('trans.disability')" :value="disabilityStatus" />
            <LabelValue :label="$t('trans.phone_number')" :value="application?.attributes?.phoneNumber ?? ''" />
            <LabelValue :label="$t('trans.email_address')" :value="application?.attributes?.email ?? ''" />
        </div>
    </BaseCard>
    <BaseCard :title="$tChoice('trans.course', 1) + ' details'" description="Course specific details">
        <div class="grid grid-cols-2 gap-3">
            <LabelValue :label="$tChoice('trans.department', 1)" :value="application?.attributes?.department ?? ''" />
            <LabelValue :label="$tChoice('trans.level', 1)" :value="application?.attributes?.level ?? ''" />
            <LabelValue :label="$tChoice('trans.course', 1)" :value="application?.attributes?.course ?? ''" />
            <LabelValue :label="$tChoice('trans.intake_period', 1)" :value="application?.attributes?.intakePeriod ?? ''" />
            <LabelValue :label="$tChoice('trans.mode_of_study', 1)" :value="application?.attributes?.modeOfStudy ?? ''" />
            <LabelValue :label="$tChoice('trans.tracking_number', 1)" :value="application?.attributes?.applicationTrackingNumber ?? ''" />
        </div>
    </BaseCard>
    <BaseCard :title="$t('trans.o_level_subjects')" v-if="oLevelRequired" description="Provided o level results">
        <div class="grid grid-cols-2 gap-3">
            <LabelValue
                v-for="result in application?.relationships?.oLevelResults ?? []"
                :label="result.attributes.subject"
                :value="`${result.attributes.grade} (${result.attributes.examYear})`"
                :key="result.id"
            />
        </div>
    </BaseCard>
    <BaseCard
        v-if="previousLevelRequired"
        :title="$t('trans.level_required', { level: requiredLevel ?? '' })"
        description="Previous level completed by the applicant"
    >
        <LabelValue
            :value-classes="badgeClass"
            :label="`Completed ${requiredLevel ?? ''}`"
            :value="yesOrNo(isItTrue(application?.attributes?.requiredLevelCompleted))"
        />
    </BaseCard>
    <BaseCard v-if="readWriteRequired" title="Read & Write Requirement" description="Applicant should be able to read and write">
        <LabelValue
            :value-classes="badgeClass"
            label="Read & Write Confirmed"
            :value="yesOrNo(isItTrue(application?.attributes?.readWriteAcknowledged))"
        />
    </BaseCard>
</template>

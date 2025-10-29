<script setup lang="ts">
import LabelValue from '@/components/core/util/LabelValue.vue';
import { AuthObject } from '@/types/data-pagination';
import { Enrolment } from '@/types/enrolments';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { useUtils } from '@/composables/core/useUtils';
import { computed } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    application: Enrolment;
}

const props = defineProps<Props>();

const { application } = props;

const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    {
        title: application.attributes.department,
        href: route('enrolments.department-applications', { institution_department: String(application?.attributes.institutionDepartmentId) }),
    },
    {
        title: application.attributes.level,
        href: route('enrolments.department-applications', { institution_department: String(application?.attributes.institutionDepartmentId) }),
    },
    {
        title: application.attributes.course,
        href: route('enrolments.class-lists', {
            institution_department: String(application?.attributes.institutionDepartmentId),
            department_level: String(application?.attributes.departmentLevelId),
            intake_period_id: String(application?.attributes.intakePeriodId),
            mode_of_study_id: String(application?.attributes.modeOfStudyId),
            department_course_id: String(application?.attributes.departmentCourseId),
        }),
    },
    { title: application?.attributes?.studentName },
];

const badgeClass = 'rounded-full px-3 py-.05 bg-persian-200 text-primary w-fit';
const {isItTrue, yesOrNo} = useUtils();

const oLevelRequired = computed(() => {
    if(application?.relationships?.requirements) {
        return isItTrue(application?.relationships?.requirements?.attributes?.isOLevelRequired);
    }

    if(application?.relationships?.courseRequirements) {
        return isItTrue(application?.relationships?.courseRequirements?.attributes?.isOLevelRequired)
    }
    return false;
})
const previousLevelRequired = computed(() => {
    if(application?.relationships?.requirements) {
        return Number(application?.relationships?.requirements?.attributes?.requiredLevelId) > 0;
    }

    if(application?.relationships?.courseRequirements) {
        return Number(application?.relationships?.courseRequirements?.attributes?.requiredLevelId) > 0;
    }
    return false;
})

const requiredLevel = computed(() => {
    if(application?.relationships?.requirements) {
        return application?.relationships?.requirements?.attributes?.requiredLevel ?? '---';
    }

    if(application?.relationships?.courseRequirements) {
        return application?.relationships?.courseRequirements?.attributes?.requiredLevel ?? '---';
    }
    return '---';
})
</script>

<template>
    <Head :title="$tChoice('trans.enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex justify-between space-x-3">
            <div class="flex w-3/3 flex-col space-y-3">
                <BaseCard :title="$t('trans.personal')" description="Personl details of the applicant">
                    <div class="grid grid-cols-3 gap-3">
                        <LabelValue :value-classes="badgeClass" :label="$tChoice('trans.name', 1)" :value="application?.attributes?.studentName" />
                        <LabelValue :label="$tChoice('trans.id_type', 1)" :value="application?.attributes?.idType ?? '---'" />
                        <LabelValue :value-classes="badgeClass" :label="$tChoice('trans.id_number', 1)" :value="application?.attributes?.idNumber ?? '---'" />
                        <LabelValue :value-classes="badgeClass" :label="$t('trans.disability')" :value="application?.attributes?.disabilityStatus ?? ''" />
                        <LabelValue :label="$t('trans.phone_number')" :value="application?.attributes?.phoneNumber ?? ''" />
                        <LabelValue :label="$t('trans.email_address')" :value="application?.attributes?.email ?? ''" />
                    </div>
                </BaseCard>
                <BaseCard :title="$tChoice('trans.course', 1) + ' details'" description="Course specific details">
                    <div class="grid grid-cols-3 gap-3">
                        <LabelValue :label="$tChoice('trans.department', 1)" :value="application?.attributes?.department ?? ''" />
                        <LabelValue :label="$tChoice('trans.level', 1)" :value="application?.attributes?.level ?? ''" />
                        <LabelValue :label="$tChoice('trans.course', 1)" :value="application?.attributes?.course ?? ''" />
                        <LabelValue :label="$tChoice('trans.intake_period', 1)" :value="application?.attributes?.intakePeriod ?? ''" />
                        <LabelValue :label="$tChoice('trans.mode_of_study', 1)" :value="application?.attributes?.modeOfStudy ?? ''" />
                        <LabelValue :label="$tChoice('trans.tracking_number', 1)" :value="application?.attributes?.applicationTrackingNumber ?? ''" />
                    </div>
                </BaseCard>
                <BaseCard :title="$t('trans.o_level_subjects')" v-if="oLevelRequired" description="Provided o level results">
                    <div class="grid grid-cols-3 gap-3">
                        <LabelValue
                            v-for="result in application?.relationships?.oLevelResults ?? []"
                            :label="result.attributes.subject"
                            :value="result.attributes.grade ?? '---'"
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
            </div>
            <div class="flex w-1/4 flex-col">Other Applications</div>
        </div>
    </PageContainer>
</template>

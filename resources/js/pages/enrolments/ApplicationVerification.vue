<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { forbiddenAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import { AuthObject } from '@/types/data-pagination';
import { ClassListAttributeParams, ClassListTopNext, Enrolment, OtherApplication } from '@/types/enrolments';
import { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    application: Enrolment;
    nextTop: ClassListTopNext[];
    otherApplications: OtherApplication[];
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

const badgeClass = 'rounded-full px-3 py-.05 bg-green-100 text-green-600 w-fit';
const { isItTrue, yesOrNo, isNativeCitizen } = useUtils();

const oLevelRequired = computed(() => {
    if (application?.relationships?.requirements) {
        return isItTrue(application?.relationships?.requirements?.attributes?.isOLevelRequired);
    }
    if (application?.relationships?.courseRequirements) {
        return isItTrue(application?.relationships?.courseRequirements?.attributes?.isOLevelRequired);
    }
    return false;
});

const previousLevelRequired = computed(() => {
    if (application?.relationships?.requirements) {
        return Number(application?.relationships?.requirements?.attributes?.requiredLevelId) > 0;
    }
    if (application?.relationships?.courseRequirements) {
        return Number(application?.relationships?.courseRequirements?.attributes?.requiredLevelId) > 0;
    }
    return false;
});
const readWriteRequired = computed(() => {
    if (application?.relationships?.requirements) {
        return isItTrue(application?.relationships?.requirements?.attributes?.onlyReadWriteRequired);
    }

    if (application?.relationships?.courseRequirements) {
        return isItTrue(application?.relationships?.courseRequirements?.attributes?.onlyReadWriteRequired);
    }
    return false;
});
const requiredLevel = computed(() => {
    if (application?.relationships?.requirements) {
        return application?.relationships?.requirements?.attributes?.requiredLevel ?? '---';
    }
    if (application?.relationships?.courseRequirements) {
        return application?.relationships?.courseRequirements?.attributes?.requiredLevel ?? '---';
    }
    return '---';
});

const disabilityStatus = computed(() => {
    if (application?.attributes?.disabilityStatus === 'prefer_not_to_say') {
        return 'Prefer not to say';
    } else {
        return yesOrNo(isItTrue(application?.attributes?.disabilityStatus));
    }
});

const form = useForm<ClassListAttributeParams>({
    identity_confirmed: false,
    disability_confirmed: false,
    names_confirmed: false,
    o_level_confirmed: false,
    previous_level_confirmed: false,
    read_write_confirmed: false,
    application_fee_confirmed: false,
    tuition_fee_confirmed: false,
});
const saveVerification = async () => {
    if (!hasAbility('verify:class-lists')) {
        forbiddenAlert();
        return;
    }
    const confirmed = await useCustomConfirmDialog().open({
        title: 'Verify Applicant',
        message: 'Are you sure you are confirming the details of this applicant?',
        confirmText: 'Yes confirm',
    });
};
</script>

<template>
    <Head :title="$tChoice('trans.enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="flex justify-between space-x-3">
            <div class="flex w-3/3 flex-col space-y-3">
                <BaseCard :title="$t('trans.personal')" description="Personal details of the applicant">
                    <div class="grid grid-cols-3 gap-3">
                        <LabelValue :value-classes="badgeClass" :label="$tChoice('trans.name', 1)" :value="application?.attributes?.studentName" />
                        <LabelValue :label="$tChoice('trans.id_type', 1)" :value="application?.attributes?.idType ?? '---'" />
                        <template v-if="isNativeCitizen(application?.attributes?.idType ?? '')">
                            <LabelValue
                                :value-classes="badgeClass"
                                :label="$tChoice('trans.id_number', 1)"
                                :value="application?.attributes?.idNumber ?? '---'"
                            />
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
                <BaseCard title="Verification" description="Verification of applicant details">
                    <div class="grid grid-cols-1 gap-4">
                        <BaseCheckbox
                            input-id="identity_confirmed"
                            v-model="form.identity_confirmed"
                            label="Confirm Identity is correct(id number / passport number)"
                        />
                        <BaseCheckbox input-id="names_confirmed" v-model="form.names_confirmed" label="Confirm applicant's name is correct" />
                        <BaseCheckbox
                            input-id="disability_confirmed"
                            v-model="form.disability_confirmed"
                            label="Confirm applicant's disability status"
                        />
                        <BaseCheckbox
                            v-if="oLevelRequired"
                            input-id="o_level_confirmed"
                            v-model="form.o_level_confirmed"
                            label="Confirm O Level results are correct"
                        />
                        <BaseCheckbox
                            v-if="previousLevelRequired"
                            input-id="previous_level_confirmed"
                            v-model="form.previous_level_confirmed"
                            :label="`Confirm ${requiredLevel} level completed`"
                        />
                        <BaseCheckbox
                            v-if="readWriteRequired"
                            input-id="read_write_confirmed"
                            v-model="form.read_write_confirmed"
                            label="Confirm read and write ability"
                        />
                    </div>
                    <div class="mt-6 flex items-center justify-between">
                        <BaseButton title="Verify details and Give Offer To applicant" @click="saveVerification" classes="rounded-full" />
                        <BaseButton :variant="ColorVariant.danger" title="Reject Application" @click="saveVerification" classes="rounded-full" />
                    </div>
                </BaseCard>
            </div>
            <div class="flex w-1/4 flex-col space-y-15">
                <div class="flex flex-col space-y-3" v-if="otherApplications && otherApplications.length > 0">
                    <HeadingSmall title="Other applications" description="Applicant has other applications in other departments" />
                    <div class="flex flex-col space-y-2">
                        <div
                            v-for="application in otherApplications"
                            :key="application.applicationId"
                            class="text-accent-foreground flex flex-col rounded-md border-r-2 border-black bg-gray-200 px-3 py-2 text-[8px] uppercase"
                        >
                            <div class="flex justify-between">
                                <div>{{ application.department }}</div>
                                <div class="bg-primary text-persian-200 rounded-full px-2 py-0.5">{{ application.level }}</div>
                            </div>
                            <div class="my-1 flex justify-between">
                                <div>{{ application.course }}</div>
                                <div>{{ application.modeOfStudy }}</div>
                            </div>
                            <div class="flex">
                                {{ application.inClassList ? 'In Class List' : 'Not in Class List' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col space-y-3" v-if="nextTop && nextTop.length > 0">
                    <HeadingSmall title="Next 10" description="Other applications awaiting verification in this class list" />
                    <div class="flex flex-col space-y-2">
                        <TextLink
                            classes="bg-gray-200 px-3 py-2 rounded-md text-xs uppercase text-accent-foreground border-r-2 border-black"
                            v-for="application in nextTop"
                            :key="application.applicationId"
                            :title="application.name"
                            :href="route('enrolments.verify', { student_program: application.applicationId })"
                        />
                    </div>
                </div>
            </div>
        </div>
    </PageContainer>
</template>

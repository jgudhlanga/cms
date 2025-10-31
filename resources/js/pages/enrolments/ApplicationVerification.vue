<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { forbiddenAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import Details from '@/pages/enrolments/partials/verification/Details.vue';
import Sidebar from '@/pages/enrolments/partials/verification/Sidebar.vue';
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

const { isItTrue } = useUtils();

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
                <Details
                    :application="application"
                    :o-level-required="oLevelRequired"
                    :previous-level-required="previousLevelRequired"
                    :required-level="requiredLevel"
                    :read-write-required="readWriteRequired"
                />
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
                <Sidebar :other-applications="otherApplications" :next-top="nextTop" />
            </div>
        </div>
    </PageContainer>
</template>

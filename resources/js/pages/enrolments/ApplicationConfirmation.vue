<script setup lang="ts">
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, forbiddenAlert, successAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import Details from '@/pages/enrolments/partials/verification/Details.vue';
import Sidebar from '@/pages/enrolments/partials/verification/Sidebar.vue';
import { AuthObject } from '@/types/data-pagination';
import { ClassListAttributeParams, ClassListTopNext, Enrolment, OtherApplication } from '@/types/enrolments';
import { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    application: Enrolment;
    nextTop: ClassListTopNext[];
    otherApplications: OtherApplication[];
}

const props = defineProps<Props>();

const { application, nextTop } = props;

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

const { isItTrue, navigateTo } = useUtils();

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
    identity_confirmed: null,
    disability_confirmed: null,
    names_confirmed: null,
    o_level_confirmed: null,
    previous_level_confirmed: null,
    read_write_confirmed: null,
    application_fee_confirmed: null,
    tuition_fee_confirmed: null,
});
const saveVerification = async () => {
    if (!hasAbility('verify:class-lists')) {
        forbiddenAlert();
        return;
    }
    if (!form.identity_confirmed) {
        errorAlert('Please confirm Identity is correct');
        return;
    }
    if (!form.names_confirmed) {
        errorAlert('Please confirm Name is correct');
        return;
    }
    if (!form.disability_confirmed) {
        errorAlert('Please confirm disability status is correct');
        return;
    }
    if (oLevelRequired.value) {
        if (!form.o_level_confirmed) {
            errorAlert('Please confirm O Level results are correct');
            return;
        }
    }
    if (previousLevelRequired.value) {
        if (!form.previous_level_confirmed) {
            errorAlert(`Please confirm ${requiredLevel.value} level completed`);
            return;
        }
    }
    if (readWriteRequired.value) {
        if (!form.read_write_confirmed) {
            errorAlert(trans('trans.acknowledge_read_write'));
            return;
        }
    }
    const confirmed = await useCustomConfirmDialog().open({
        title: 'Verify Applicant',
        message: 'Are you sure you are confirming the details of this applicant?',
        confirmText: 'Yes confirm',
    });
    if (confirmed) {
        form.put(route('enrolments.update-class-list', { student_program: String(application.id) }), {
            onSuccess: () => {
                successAlert('Class list entry successfully verified.');
                if (nextTop.length > 0) {
                    navigateTo(route('enrolments.verify', { student_program: String(nextTop[0].applicationId) }));
                }
            },
            onError: (errors: any) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert('An unexpected error happened, class list entry could not be verified.');
                }
            },
        });
    }
};
const rejectApplication = async () => {
    if (!hasAbility('verify:class-lists')) {
        forbiddenAlert();
        return;
    }
    const confirmed = await useCustomConfirmDialog().open({
        title: 'Reject Applicant',
        message: 'Are you sure you are rejecting this applicant from the class list?',
        confirmText: 'Yes confirm',
    });
    if (confirmed) {
        form.put(route('enrolments.reject-application', { student_program: String(application.id) }), {
            onSuccess: () => {
                successAlert('Application successfully rejected from class list.');
                if (nextTop.length > 0) {
                    navigateTo(route('enrolments.verify', { student_program: String(nextTop[0].applicationId) }));
                }
            },
            onError: (errors: any) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert('An unexpected error happened, class list entry could not be rejected.');
                }
            },
        });
    }
};

const identityConfirmedOptions = computed(() => [
    { inputId: 'identity_confirmed_yes', label: 'Yes', value: true },
    { inputId: 'identity_confirmed_no', label: 'No', value: false },
]);
const namesConfirmedOptions = computed(() => [
    { inputId: 'names_confirmed_yes', label: 'Yes', value: true },
    { inputId: 'names_confirmed_no', label: 'No', value: false },
]);
const disabilityConfirmedOptions = computed(() => [
    { inputId: 'disability_confirmed_yes', label: 'Yes', value: true },
    { inputId: 'disability_confirmed_no', label: 'No', value: false },
]);
const oLevelConfirmedOptions = computed(() => [
    { inputId: 'o_level_confirmed_yes', label: 'Yes', value: true },
    { inputId: 'o_level_confirmed_no', label: 'No', value: false },
]);
const previousLevelOptions = computed(() => [
    { inputId: 'previous_level_confirmed_yes', label: 'Yes', value: true },
    { inputId: 'previous_level_confirmed_no', label: 'No', value: false },
]);
const readWriteOptions = computed(() => [
    { inputId: 'read_write_confirmed_yes', label: 'Yes', value: true },
    { inputId: 'read_write_confirmed_no', label: 'No', value: false },
]);

onMounted(() => {
    const entry = application.relationships?.classList;
    form.identity_confirmed = isItTrue(entry?.attributes?.identityConfirmed);
    form.names_confirmed = isItTrue(entry?.attributes?.namesConfirmed);
    form.disability_confirmed = isItTrue(entry?.attributes?.disabilityConfirmed);
    form.o_level_confirmed = isItTrue(entry?.attributes?.oLevelConfirmed);
    form.previous_level_confirmed = isItTrue(entry?.attributes?.previousLevelConfirmed);
    form.read_write_confirmed = isItTrue(entry?.attributes?.readWriteConfirmed);
});
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
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">Confirm Identity is correct(id number / passport number):</Label>
                            <BaseRadioGroup :options="identityConfirmedOptions" v-model="form.identity_confirmed" :vertical-layout="false" />
                        </div>
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">Confirm applicant's name is correct:</Label>
                            <BaseRadioGroup :options="namesConfirmedOptions" v-model="form.names_confirmed" :vertical-layout="false" />
                        </div>
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">Confirm applicant's disability status:</Label>
                            <BaseRadioGroup :options="disabilityConfirmedOptions" v-model="form.disability_confirmed" :vertical-layout="false" />
                        </div>
                        <div class="flex items-center space-x-5" v-if="oLevelRequired">
                            <Label class="font-bold">Confirm O Level results are correct:</Label>
                            <BaseRadioGroup :options="oLevelConfirmedOptions" v-model="form.o_level_confirmed" :vertical-layout="false" />
                        </div>
                        <div class="flex items-center space-x-5" v-if="previousLevelRequired">
                            <Label class="font-bold">{{ `Confirm ${requiredLevel} level completed` }}</Label>
                            <BaseRadioGroup :options="previousLevelOptions" v-model="form.previous_level_confirmed" :vertical-layout="false" />
                        </div>
                        <div class="flex items-center space-x-5" v-if="readWriteRequired">
                            <Label class="font-bold">Confirm read and write ability</Label>
                            <BaseRadioGroup :options="readWriteOptions" v-model="form.read_write_confirmed" :vertical-layout="false" />
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-between">
                        <BaseButton title="Verify and give Offer to applicant" @click="saveVerification" classes="rounded-full" />
                        <BaseButton :variant="ColorVariant.danger" title="Reject Application" @click="rejectApplication" classes="rounded-full" />
                    </div>
                </BaseCard>
            </div>
            <div class="flex w-1/4 flex-col space-y-15">
                <Sidebar :other-applications="otherApplications" :next-top="nextTop" />
            </div>
        </div>
    </PageContainer>
</template>

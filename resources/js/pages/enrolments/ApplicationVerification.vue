<script setup lang="ts">
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, forbiddenAlert, successAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import Details from '@/pages/enrolments/partials/shared/Details.vue';
import RejectApplicationButton from '@/pages/enrolments/partials/shared/RejectApplicationButton.vue';
import Sidebar from '@/pages/enrolments/partials/shared/Sidebar.vue';
import { AuthObject } from '@/types/data-pagination';
import { ClassListAttributeParams, ClassListTopNext, ClassListType, Enrolment, OtherApplication } from '@/types/enrolments';
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
const { isItTrue, navigateTo, getQueryParams } = useUtils();
const queryParams = getQueryParams();

const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    {
        title: application.attributes.department,
        href: route('enrolments.department-applications', { institution_department: String(application?.attributes.institutionDepartmentId) }),
    },
    {
        title: application.attributes.level,
        href: route('enrolments.department-applications', {
            institution_department: String(application?.attributes.institutionDepartmentId),
            type: queryParams['type'],
        }),
    },
    {
        title: application.attributes.course,
        href: route('enrolments.class-lists', {
            institution_department: String(application?.attributes.institutionDepartmentId),
            department_level: String(application?.attributes.departmentLevelId),
            intake_period_id: String(application?.attributes.intakePeriodId),
            mode_of_study_id: String(application?.attributes.modeOfStudyId),
            department_course_id: String(application?.attributes.departmentCourseId),
            type: queryParams['type'],
        }),
    },
    { title: application?.attributes?.studentName },
];

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
        return application?.relationships?.requirements?.attributes?.requiredLevel ?? trans('enrolments.not_applicable_level');
    }
    if (application?.relationships?.courseRequirements) {
        return application?.relationships?.courseRequirements?.attributes?.requiredLevel ?? trans('enrolments.not_applicable_level');
    }
    return trans('enrolments.not_applicable_level');
});

const previousLevelLabel = computed(() => trans('enrolments.label_confirm_previous_level', { level: requiredLevel.value }));

const nextVerifyHref = computed(() =>
    nextTop.length > 0 ? route('enrolments.verify', { student_program: String(nextTop[0].applicationId) }) : null,
);

const form = useForm<ClassListAttributeParams>({
    identity_confirmed: null,
    disability_confirmed: null,
    names_confirmed: null,
    o_level_confirmed: null,
    previous_level_confirmed: null,
    read_write_confirmed: null,
    application_fee_confirmed: null,
    proof_of_payment_confirmed: null,
    passport_photos_confirmed: null,
    original_birth_certificate_confirmed: null,
    original_national_identity_confirmed: null,
    original_education_certificates_confirmed: null,
    type: (queryParams['type'] as ClassListType) ?? 'provisional',
});
const saveVerification = async () => {
    if (!hasAbility('verify:class-lists')) {
        forbiddenAlert();
        return;
    }
    if (!form.identity_confirmed) {
        errorAlert(trans('enrolments.error_identity'));
        return;
    }
    if (!form.names_confirmed) {
        errorAlert(trans('enrolments.error_names'));
        return;
    }
    if (!form.disability_confirmed) {
        errorAlert(trans('enrolments.error_disability'));
        return;
    }
    if (oLevelRequired.value) {
        if (!form.o_level_confirmed) {
            errorAlert(trans('enrolments.error_o_level'));
            return;
        }
    }
    if (previousLevelRequired.value) {
        if (!form.previous_level_confirmed) {
            errorAlert(trans('enrolments.error_previous_level', { level: requiredLevel.value }));
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
        title: trans('enrolments.verify_dialog_title'),
        message: trans('enrolments.verify_dialog_message'),
        confirmText: trans('enrolments.confirm_action'),
    });
    if (confirmed) {
        form.put(route('enrolments.update-class-list', { student_program: String(application.id) }), {
            onSuccess: () => {
                successAlert(trans('enrolments.success_verified'));
                if (nextVerifyHref.value) {
                    navigateTo(nextVerifyHref.value);
                }
            },
            onError: (errors: Record<string, string | string[]>) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert(trans('enrolments.error_verify_unexpected'));
                }
            },
        });
    }
};

const yesNo = () => ({ yes: trans('trans.yes'), no: trans('trans.no') });

const identityConfirmedOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'identity_confirmed_yes', label: yes, value: true },
        { inputId: 'identity_confirmed_no', label: no, value: false },
    ];
});
const namesConfirmedOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'names_confirmed_yes', label: yes, value: true },
        { inputId: 'names_confirmed_no', label: no, value: false },
    ];
});
const disabilityConfirmedOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'disability_confirmed_yes', label: yes, value: true },
        { inputId: 'disability_confirmed_no', label: no, value: false },
    ];
});
const oLevelConfirmedOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'o_level_confirmed_yes', label: yes, value: true },
        { inputId: 'o_level_confirmed_no', label: no, value: false },
    ];
});
const previousLevelOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'previous_level_confirmed_yes', label: yes, value: true },
        { inputId: 'previous_level_confirmed_no', label: no, value: false },
    ];
});
const readWriteOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'read_write_confirmed_yes', label: yes, value: true },
        { inputId: 'read_write_confirmed_no', label: no, value: false },
    ];
});

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
                <BaseCard :title="$t('enrolments.verification_card_title')" :description="$t('enrolments.verification_card_description')">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">{{ $t('enrolments.label_confirm_identity') }}</Label>
                            <BaseRadioGroup :options="identityConfirmedOptions as any" v-model="form.identity_confirmed" :vertical-layout="false" />
                        </div>
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">{{ $t('enrolments.label_confirm_names') }}</Label>
                            <BaseRadioGroup :options="namesConfirmedOptions as any" v-model="form.names_confirmed" :vertical-layout="false" />
                        </div>
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">{{ $t('enrolments.label_confirm_disability') }}</Label>
                            <BaseRadioGroup
                                :options="disabilityConfirmedOptions as any"
                                v-model="form.disability_confirmed"
                                :vertical-layout="false"
                            />
                        </div>
                        <div class="flex items-center space-x-5" v-if="oLevelRequired">
                            <Label class="font-bold">{{ $t('enrolments.label_confirm_o_level') }}</Label>
                            <BaseRadioGroup :options="oLevelConfirmedOptions as any" v-model="form.o_level_confirmed" :vertical-layout="false" />
                        </div>
                        <div class="flex items-center space-x-5" v-if="previousLevelRequired">
                            <Label class="font-bold">{{ previousLevelLabel }}</Label>
                            <BaseRadioGroup :options="previousLevelOptions as any" v-model="form.previous_level_confirmed" :vertical-layout="false" />
                        </div>
                        <div class="flex items-center space-x-5" v-if="readWriteRequired">
                            <Label class="font-bold">{{ $t('enrolments.label_confirm_read_write') }}</Label>
                            <BaseRadioGroup :options="readWriteOptions as any" v-model="form.read_write_confirmed" :vertical-layout="false" />
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-between">
                        <BaseButton :title="$t('enrolments.button_verify_and_offer')" @click="saveVerification" />
                        <RejectApplicationButton
                            :student-program-id="String(application.id)"
                            :form="form"
                            required-ability="verify:class-lists"
                            :next-href="nextVerifyHref"
                        />
                    </div>
                </BaseCard>
            </div>
            <div class="flex w-1/4 flex-col space-y-15">
                <Sidebar :other-applications="otherApplications" :next-top="nextTop" :type="'provisional' as ClassListType" />
            </div>
        </div>
    </PageContainer>
</template>

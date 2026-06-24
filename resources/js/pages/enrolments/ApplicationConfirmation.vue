<script setup lang="ts">
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { TextFieldType } from '@/enums/inputs';
import { errorAlert, forbiddenAlert, successAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import Details from '@/pages/enrolments/partials/shared/Details.vue';
import FeeInvoice from '@/pages/enrolments/partials/shared/FeeInvoice.vue';
import FeeReceipt from '@/pages/enrolments/partials/shared/FeeReceipt.vue';
import RejectApplicationButton from '@/pages/enrolments/partials/shared/RejectApplicationButton.vue';
import Sidebar from '@/pages/enrolments/partials/shared/Sidebar.vue';
import { AuthObject } from '@/types/data-pagination';
import { ClassListAttributeParams, ClassListTopNext, ClassListType, Enrolment } from '@/types/enrolments';
import { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    application: Enrolment;
    nextTop: ClassListTopNext[];
    tuition?: string | number;
    autoCardFee?: string | number;
    partTimeLevy?: string | number;
}

const props = defineProps<Props>();
const { isItTrue, navigateTo, getQueryParams } = useUtils();
const queryParams = getQueryParams();

const { application, nextTop } = props;

const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    {
        title: application.attributes.department,
        href: route('enrolments.department-applications', {
            institution_department: String(application?.attributes.institutionDepartmentId),
            type: queryParams['type'],
        }),
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

const nextConfirmHref = computed(() =>
    nextTop.length > 0 ? route('enrolments.confirm', { student_application: String(nextTop[0].applicationId), type: queryParams['type'] }) : null,
);

const form = useForm<ClassListAttributeParams>({
    identity_confirmed: null,
    disability_confirmed: null,
    names_confirmed: null,
    o_level_confirmed: null,
    previous_level_confirmed: null,
    read_write_confirmed: null,
    application_fee_confirmed: null,
    proof_of_payment_confirmed: false,
    passport_photos_confirmed: null,
    original_birth_certificate_confirmed: null,
    original_national_identity_confirmed: null,
    original_education_certificates_confirmed: null,
    type: (queryParams['type'] as ClassListType) ?? 'verified',
    remarks: null,
});

const yesNo = () => ({ yes: trans('trans.yes'), no: trans('trans.no') });

const proofOfPaymentOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'confirm_proof_of_payment_yes', label: yes, value: true },
        { inputId: 'confirm_proof_of_payment_no', label: no, value: false },
    ];
});

const passportPhotoOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'passport_photos_confirmed_yes', label: yes, value: true },
        { inputId: 'passport_photos_confirmed_no', label: no, value: false },
    ];
});

const birthCertificateOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'original_birth_certificate_confirmed_yes', label: yes, value: true },
        { inputId: 'original_birth_certificate_confirmed_no', label: no, value: false },
    ];
});

const identityOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'original_national_identity_confirmed_yes', label: yes, value: true },
        { inputId: 'original_national_identity_confirmed_no', label: no, value: false },
    ];
});
const educationCertificatesOptions = computed(() => {
    const { yes, no } = yesNo();
    return [
        { inputId: 'original_education_certificates_confirmed_yes', label: yes, value: true },
        { inputId: 'original_education_certificates_confirmed_no', label: no, value: false },
    ];
});

const saveConfirmation = async () => {
    if (!hasAbility('manage-final:class-lists')) {
        forbiddenAlert();
        return;
    }
    if (!form.passport_photos_confirmed) {
        errorAlert(trans('enrolments.error_passport_photos'));
        return;
    }
    if (!form.original_birth_certificate_confirmed) {
        errorAlert(trans('enrolments.error_birth_certificate'));
        return;
    }
    if (!form.original_national_identity_confirmed) {
        errorAlert(trans('enrolments.error_national_identity'));
        return;
    }
    if (!form.original_education_certificates_confirmed) {
        errorAlert(trans('enrolments.error_education_certificates'));
        return;
    }

    const confirmed = await useCustomConfirmDialog().open({
        title: trans('enrolments.confirm_student_dialog_title'),
        message: trans('enrolments.confirm_student_dialog_message'),
        confirmText: trans('enrolments.confirm_action'),
    });
    if (confirmed) {
        form.put(route('enrolments.update-class-list', { student_application: String(application.id) }), {
            onSuccess: () => {
                successAlert(trans('enrolments.success_student_confirmed'));
                if (nextConfirmHref.value) {
                    navigateTo(nextConfirmHref.value);
                }
            },
            onError: (errors: Record<string, string | string[]>) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert(trans('enrolments.error_confirm_unexpected'));
                }
            },
        });
    }
};

onMounted(() => {
    const entry = application.relationships?.classList;
    form.identity_confirmed = isItTrue(entry?.attributes?.identityConfirmed);
    form.names_confirmed = isItTrue(entry?.attributes?.namesConfirmed);
    form.disability_confirmed = isItTrue(entry?.attributes?.disabilityConfirmed);
    form.o_level_confirmed = isItTrue(entry?.attributes?.oLevelConfirmed);
    form.previous_level_confirmed = isItTrue(entry?.attributes?.previousLevelConfirmed);
    form.read_write_confirmed = isItTrue(entry?.attributes?.readWriteConfirmed);
    form.proof_of_payment_confirmed = isItTrue(entry?.attributes?.proofOfPaymentConfirmed);
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
                <BaseCard :title="$t('enrolments.confirmation_card_title')" :description="$t('enrolments.confirmation_card_description')">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">{{ $t('enrolments.label_proof_of_payment') }}</Label>
                            <BaseRadioGroup
                                :options="proofOfPaymentOptions as any"
                                v-model="form.proof_of_payment_confirmed"
                                :vertical-layout="false"
                            />
                        </div>
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">{{ $t('enrolments.label_passport_photos') }}</Label>
                            <BaseRadioGroup
                                :options="passportPhotoOptions as any"
                                v-model="form.passport_photos_confirmed"
                                :vertical-layout="false"
                            />
                        </div>
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">{{ $t('enrolments.label_birth_certificate') }}</Label>
                            <BaseRadioGroup
                                :options="birthCertificateOptions as any"
                                v-model="form.original_birth_certificate_confirmed"
                                :vertical-layout="false"
                            />
                        </div>
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">{{ $t('enrolments.label_national_identity') }}</Label>
                            <BaseRadioGroup
                                :options="identityOptions as any"
                                v-model="form.original_national_identity_confirmed"
                                :vertical-layout="false"
                            />
                        </div>
                        <div class="flex items-center space-x-5">
                            <Label class="font-bold">{{ $t('enrolments.label_education_certificates') }}</Label>
                            <BaseRadioGroup
                                :options="educationCertificatesOptions as any"
                                v-model="form.original_education_certificates_confirmed"
                                :vertical-layout="false"
                            />
                        </div>
                    </div>
                    <div class="flex w-full">
                        <BaseInput
                            input-id="remarks"
                            :label="`${$t('general.remarks')}:`"
                            :placeholder="$t('enrolments.remarks_placeholder')"
                            :type="TextFieldType.text"
                            v-model="form.remarks"
                        />
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <BaseButton :title="$t('enrolment.add_student_to_class')" @click="saveConfirmation" />
                        <RejectApplicationButton
                            :student-application-id="String(application.id)"
                            :form="form"
                            required-ability="manage-final:class-lists"
                            :next-href="nextConfirmHref"
                        />
                    </div>
                </BaseCard>
            </div>
            <div class="flex w-1/4 flex-col space-y-10">
                <Sidebar :next-top="nextTop" :type="'verified' as ClassListType" />
                <FeeInvoice
                    v-if="tuition || autoCardFee || partTimeLevy"
                    :tuition="tuition"
                    :auto-card-fee="autoCardFee"
                    :part-time-levy="partTimeLevy"
                />
                <FeeReceipt :student-id="String(application.attributes.studentId)" :enrolment="application" />
            </div>
        </div>
    </PageContainer>
</template>

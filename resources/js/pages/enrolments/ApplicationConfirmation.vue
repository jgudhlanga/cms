<script setup lang="ts">
import EnrolmentApplicantLookupDrawer from '@/components/enrolments/EnrolmentApplicantLookupDrawer.vue';
import VerificationMatchToggle from '@/components/enrolments/VerificationMatchToggle.vue';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { TextFieldType } from '@/enums/inputs';
import { errorAlert, forbiddenAlert, successAlert } from '@/lib/alerts';
import {
    buildDepartmentApplicationsUrl,
    enrolmentStatusFromQuery,
    enrolmentStatusOriginBackUrl,
    parseEnrolmentStatusFrom,
} from '@/lib/enrolmentStatusOrigin';
import { hasAbility } from '@/lib/permissions';
import Details from '@/pages/enrolments/partials/shared/Details.vue';
import FeeInvoice from '@/pages/enrolments/partials/shared/FeeInvoice.vue';
import FeeReceipt from '@/pages/enrolments/partials/shared/FeeReceipt.vue';
import RejectApplicationButton from '@/pages/enrolments/partials/shared/RejectApplicationButton.vue';
import Sidebar from '@/pages/enrolments/partials/shared/Sidebar.vue';
import { AuthObject } from '@/types/data-pagination';
import {
    ClassListAttributeParams,
    ClassListTopNext,
    ClassListType,
    Enrolment,
    EnrolmentQueuePosition,
    OtherApplication,
} from '@/types/enrolments';
import { Link } from '@/types/ui';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Search, User } from 'lucide-vue-next';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    application: Enrolment;
    nextTop: ClassListTopNext[];
    otherApplications?: OtherApplication[];
    tuition?: string | number;
    autoCardFee?: string | number;
    partTimeLevy?: string | number;
    queue?: EnrolmentQueuePosition;
}

const props = defineProps<Props>();
const { isItTrue, navigateTo, getQueryParams } = useUtils();
const { application, nextTop } = props;
const queryParams = getQueryParams();
const listType = (queryParams['type'] as ClassListType) ?? 'verified';
const lookupOpen = ref(false);
const from = parseEnrolmentStatusFrom(queryParams['from']);
const originBackUrl = enrolmentStatusOriginBackUrl(from, String(application.attributes.intakePeriodId));
const departmentApplicationsHref = buildDepartmentApplicationsUrl({
    institutionDepartmentId: application.attributes.institutionDepartmentId,
    type: String(queryParams['type'] ?? ''),
    intakePeriodId: String(application.attributes.intakePeriodId),
    modeOfStudyId: String(application.attributes.modeOfStudyId),
    from: queryParams['from'],
});
const classListsHref = route('enrolments.class-lists', {
    institution_department: String(application.attributes.institutionDepartmentId),
    department_level: String(application.attributes.departmentLevelId),
    intake_period_id: String(application.attributes.intakePeriodId),
    mode_of_study_id: String(application.attributes.modeOfStudyId),
    department_course_id: String(application.attributes.departmentCourseId),
    type: queryParams['type'],
    ...enrolmentStatusFromQuery(queryParams),
});

const breadcrumbs: Array<Link> = [
    from === 'dashboard' ? { transKey: 'dashboard', href: originBackUrl } : { transKey: 'dashboard', href: route('dashboard') },
    ...(from === 'dashboard' ? [] : [{ transChoiceKey: 'trans.application', href: originBackUrl }]),
    { title: application.attributes.department, href: departmentApplicationsHref },
    { title: application.attributes.level, href: departmentApplicationsHref },
    { title: application.attributes.course, href: classListsHref },
    { title: application?.attributes?.studentName },
];

const programmeSubtitle = computed(() =>
    [
        application.attributes.department,
        application.attributes.level,
        application.attributes.course,
        application.attributes.modeOfStudy,
        application.attributes.intakePeriod,
    ]
        .filter(Boolean)
        .join(' · '),
);

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
    nextTop.length > 0
        ? route('enrolments.confirm', {
              student_application: String(nextTop[0].applicationId),
              type: queryParams['type'],
              ...enrolmentStatusFromQuery(queryParams),
          })
        : null,
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
    type: listType,
    remarks: null,
});

const confirmationFields = computed(() => [
    { key: 'proof_of_payment_confirmed' as const, label: trans('enrolments.confirm_label_proof_of_payment') },
    { key: 'passport_photos_confirmed' as const, label: trans('enrolments.confirm_label_passport_photos') },
    { key: 'original_birth_certificate_confirmed' as const, label: trans('enrolments.confirm_label_birth_certificate') },
    { key: 'original_national_identity_confirmed' as const, label: trans('enrolments.confirm_label_national_identity') },
    { key: 'original_education_certificates_confirmed' as const, label: trans('enrolments.confirm_label_education_certificates') },
]);

const checkedCount = computed(
    () => confirmationFields.value.filter((field) => form[field.key] === true).length,
);

const allRequiredDocumentsChecked = computed(
    () => confirmationFields.value.length > 0 && checkedCount.value === confirmationFields.value.length,
);

const canConfirm = computed(() => allRequiredDocumentsChecked.value);

const unlockHelperText = computed(() => {
    if (!allRequiredDocumentsChecked.value) {
        return trans('enrolments.confirm_unlock_helper');
    }
    return null;
});

const triState = (value: boolean | null | undefined): boolean | null => {
    if (isItTrue(value)) {
        return true;
    }

    return value === false ? false : null;
};

const saveConfirmation = async () => {
    if (!hasAbility('confirm:class-lists')) {
        forbiddenAlert();
        return;
    }
    if (!form.proof_of_payment_confirmed) {
        errorAlert(trans('enrolments.error_proof_of_payment'));
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
                const flashError = (usePage().props.flash as { error?: string | null } | undefined)?.error;
                if (typeof flashError === 'string' && flashError.length > 0) {
                    return;
                }

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
    form.identity_confirmed = triState(entry?.attributes?.identityConfirmed);
    form.names_confirmed = triState(entry?.attributes?.namesConfirmed);
    form.disability_confirmed = triState(entry?.attributes?.disabilityConfirmed);
    form.o_level_confirmed = triState(entry?.attributes?.oLevelConfirmed);
    form.previous_level_confirmed = triState(entry?.attributes?.previousLevelConfirmed);
    form.read_write_confirmed = triState(entry?.attributes?.readWriteConfirmed);
    form.proof_of_payment_confirmed = triState(entry?.attributes?.proofOfPaymentConfirmed);
    form.passport_photos_confirmed = triState(entry?.attributes?.passportPhotosConfirmed);
    form.original_birth_certificate_confirmed = triState(entry?.attributes?.originalBirthCertificateConfirmed);
    form.original_national_identity_confirmed = triState(entry?.attributes?.originalNationalIdentityConfirmed);
    form.original_education_certificates_confirmed = triState(entry?.attributes?.originalEducationCertificatesConfirmed);
});
</script>

<template>
    <Head :title="$tChoice('trans.application', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs" :back-url="classListsHref">
        <template #backNavigationLeading>
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <div
                    v-if="application.attributes.idPhotoThumbUrl"
                    class="h-10 w-10 shrink-0 overflow-hidden rounded-full border border-border bg-muted"
                >
                    <img
                        :src="application.attributes.idPhotoThumbUrl"
                        :alt="application.attributes.studentName"
                        class="h-full w-full object-cover"
                    />
                </div>
                <div
                    v-else
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-border bg-muted text-muted-foreground"
                >
                    <User class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <h2 class="truncate text-base font-semibold leading-tight sm:text-lg">
                        {{ application.attributes.studentName }}
                    </h2>
                    <p class="truncate text-xs text-muted-foreground sm:text-sm">{{ programmeSubtitle }}</p>
                </div>
            </div>
        </template>

        <template #backNavigationTrailing>
            <button
                type="button"
                class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1.5 text-xs font-semibold hover:bg-muted"
                @click="lookupOpen = true"
            >
                <Search class="h-3.5 w-3.5 shrink-0" />
                {{ $t('enrolments.find_applicant') }}
            </button>
        </template>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="flex flex-col gap-4 lg:col-span-9">
                <div class="rounded-xl border border-border bg-card shadow-xs">
                    <div class="grid grid-cols-1 lg:grid-cols-2 lg:divide-x lg:divide-border">
                        <div class="p-5 lg:p-6">
                            <Details
                                :application="application"
                                :o-level-required="oLevelRequired"
                                :previous-level-required="previousLevelRequired"
                                :required-level="requiredLevel"
                                :read-write-required="readWriteRequired"
                                embedded
                            />
                        </div>

                        <div class="flex flex-col p-5 lg:p-6">
                            <h3 class="mb-3 text-[0.65rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                {{ $t('enrolments.confirmation_card_title') }}
                            </h3>

                            <VerificationMatchToggle
                                v-for="field in confirmationFields"
                                :key="field.key"
                                :id="field.key"
                                :label="field.label"
                                v-model="form[field.key] as boolean | null"
                            />

                            <div class="mt-4">
                                <BaseInput
                                    input-id="remarks"
                                    :label="$t('general.remarks')"
                                    :placeholder="$t('enrolments.remarks_placeholder')"
                                    :type="TextFieldType.text"
                                    v-model="form.remarks"
                                />
                            </div>

                            <p class="mt-auto pt-3 text-xs text-muted-foreground">
                                {{
                                    $t('enrolments.confirmation_progress', {
                                        checked: checkedCount,
                                        total: confirmationFields.length,
                                    })
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <RejectApplicationButton
                            :student-application-id="String(application.id)"
                            :form="form"
                            required-ability="confirm:class-lists"
                            :next-href="nextConfirmHref"
                            outline
                        />
                        <BaseButton
                            :title="$t('enrolments.button_confirm_and_enrol')"
                            :disabled="!canConfirm"
                            @click="saveConfirmation"
                        />
                    </div>

                    <p v-if="unlockHelperText" class="text-left text-xs text-muted-foreground">
                        {{ unlockHelperText }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-4 lg:col-span-3">
                <Sidebar
                    :other-applications="otherApplications"
                    :next-top="nextTop"
                    :type="listType"
                    compact
                />
                <FeeInvoice
                    v-if="tuition || autoCardFee || partTimeLevy"
                    :tuition="tuition"
                    :auto-card-fee="autoCardFee"
                    :part-time-levy="partTimeLevy"
                />
                <FeeReceipt :student-id="String(application.attributes.studentId)" :enrolment="application" />
            </div>
        </div>

        <EnrolmentApplicantLookupDrawer
            v-model:open="lookupOpen"
            :list-type="listType"
            :intake-period-id="application.attributes.intakePeriodId"
            :intake-period-name="application.attributes.intakePeriod"
            :from="queryParams['from']"
            :initial-department-id="application.attributes.institutionDepartmentId"
            :initial-level-id="application.attributes.departmentLevelId"
            :initial-course-id="application.attributes.departmentCourseId"
        />
    </PageContainer>
</template>

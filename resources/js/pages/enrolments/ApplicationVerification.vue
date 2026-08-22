<script setup lang="ts">
import EnrolmentApplicantLookupDrawer from '@/components/enrolments/EnrolmentApplicantLookupDrawer.vue';
import VerificationMatchToggle from '@/components/enrolments/VerificationMatchToggle.vue';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, forbiddenAlert, successAlert } from '@/lib/alerts';
import {
    buildDepartmentApplicationsUrl,
    enrolmentStatusFromQuery,
    enrolmentStatusOriginBackUrl,
    parseEnrolmentStatusFrom,
} from '@/lib/enrolmentStatusOrigin';
import { hasAbility } from '@/lib/permissions';
import Details from '@/pages/enrolments/partials/shared/Details.vue';
import EnrolmentIdValidationBanner from '@/pages/enrolments/partials/shared/EnrolmentIdValidationBanner.vue';
import RejectApplicationButton from '@/pages/enrolments/partials/shared/RejectApplicationButton.vue';
import Sidebar from '@/pages/enrolments/partials/shared/Sidebar.vue';
import { AuthObject } from '@/types/data-pagination';
import {
    ClassListAttributeParams,
    ClassListTopNext,
    ClassListType,
    Enrolment,
    OtherApplication,
} from '@/types/enrolments';
import { Link } from '@/types/ui';
import { Head, useForm } from '@inertiajs/vue3';
import { Search, User } from 'lucide-vue-next';
import { trans } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    application: Enrolment;
    nextTop: ClassListTopNext[];
    otherApplications: OtherApplication[];
}

const props = defineProps<Props>();

const { application, nextTop } = props;
const { isItTrue, navigateTo, getQueryParams, isNativeCitizen } = useUtils();
const queryParams = getQueryParams();
const from = parseEnrolmentStatusFrom(queryParams['from']);
const listType = (queryParams['type'] as ClassListType) ?? 'provisional';
const lookupOpen = ref(false);

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

const previousLevelLabel = computed(() => trans('enrolments.label_confirm_previous_level', { level: requiredLevel.value }));

const nextVerifyHref = computed(() =>
    nextTop.length > 0
        ? route('enrolments.verify', {
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
});

const isZimbabweanApplicant = computed(() => isNativeCitizen(application.attributes.idType ?? ''));

const hasInvalidId = computed(
    () => isZimbabweanApplicant.value && application.attributes.idNumberValid === false,
);

const verificationFields = computed(() => {
    const fields: Array<{ key: keyof ClassListAttributeParams; label: string }> = [
        { key: 'identity_confirmed', label: trans('enrolments.verify_label_identity') },
        { key: 'names_confirmed', label: trans('enrolments.verify_label_names') },
        { key: 'disability_confirmed', label: trans('enrolments.verify_label_disability') },
    ];

    if (oLevelRequired.value) {
        fields.push({ key: 'o_level_confirmed', label: trans('enrolments.verify_label_o_level') });
    }
    if (previousLevelRequired.value) {
        fields.push({ key: 'previous_level_confirmed', label: previousLevelLabel.value });
    }
    if (readWriteRequired.value) {
        fields.push({ key: 'read_write_confirmed', label: trans('enrolments.label_confirm_read_write') });
    }

    return fields;
});

const unlockHelperText = computed(() => {
    if (hasInvalidId.value) {
        return trans('enrolments.verify_unlock_helper_invalid_id');
    }
    if (!allFieldsChecked.value) {
        return trans('enrolments.verify_unlock_helper');
    }
    return null;
});

const checkedCount = computed(
    () => verificationFields.value.filter((field) => form[field.key] === true).length,
);

const allFieldsChecked = computed(
    () => checkedCount.value === verificationFields.value.length && verificationFields.value.length > 0,
);

const canVerify = computed(() => allFieldsChecked.value && !hasInvalidId.value);

const saveVerification = async () => {
    if (!hasAbility('verify:class-lists')) {
        forbiddenAlert();
        return;
    }
    if (hasInvalidId.value) {
        errorAlert(trans('enrolments.verify_blocked_invalid_id'));
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
    if (oLevelRequired.value && !form.o_level_confirmed) {
        errorAlert(trans('enrolments.error_o_level'));
        return;
    }
    if (previousLevelRequired.value && !form.previous_level_confirmed) {
        errorAlert(trans('enrolments.error_previous_level', { level: requiredLevel.value }));
        return;
    }
    if (readWriteRequired.value && !form.read_write_confirmed) {
        errorAlert(trans('trans.acknowledge_read_write'));
        return;
    }

    const confirmed = await useCustomConfirmDialog().open({
        title: trans('enrolments.verify_dialog_title'),
        message: trans('enrolments.verify_dialog_message'),
        confirmText: trans('enrolments.confirm_action'),
    });

    if (confirmed) {
        form.put(route('enrolments.update-class-list', { student_application: String(application.id) }), {
            onSuccess: () => {
                successAlert(trans('enrolments.success_verified'));
                if (nextVerifyHref.value) {
                    navigateTo(nextVerifyHref.value);
                }
            },
            onError: (errors: Record<string, string | string[]>) => {
                if (Object.keys(errors).length) {
                    errorAlert(Object.values(errors).join('\n'));
                } else {
                    errorAlert(trans('enrolments.error_verify_unexpected'));
                }
            },
        });
    }
};

onMounted(() => {
    const entry = application.relationships?.classList;
    form.identity_confirmed = isItTrue(entry?.attributes?.identityConfirmed) ? true : entry?.attributes?.identityConfirmed === false ? false : null;
    form.names_confirmed = isItTrue(entry?.attributes?.namesConfirmed) ? true : entry?.attributes?.namesConfirmed === false ? false : null;
    form.disability_confirmed = isItTrue(entry?.attributes?.disabilityConfirmed) ? true : entry?.attributes?.disabilityConfirmed === false ? false : null;
    form.o_level_confirmed = isItTrue(entry?.attributes?.oLevelConfirmed) ? true : entry?.attributes?.oLevelConfirmed === false ? false : null;
    form.previous_level_confirmed = isItTrue(entry?.attributes?.previousLevelConfirmed) ? true : entry?.attributes?.previousLevelConfirmed === false ? false : null;
    form.read_write_confirmed = isItTrue(entry?.attributes?.readWriteConfirmed) ? true : entry?.attributes?.readWriteConfirmed === false ? false : null;
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
                <EnrolmentIdValidationBanner :application="application" />

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
                                highlight-under-review
                            />
                        </div>

                        <div class="flex flex-col p-5 lg:p-6">
                            <h3 class="mb-3 text-[0.65rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                                {{ $t('enrolments.verification_card_title') }}
                            </h3>

                            <VerificationMatchToggle
                                v-for="field in verificationFields"
                                :key="field.key"
                                :id="field.key"
                                :label="field.label"
                                v-model="form[field.key] as boolean | null"
                            />

                            <p class="mt-auto pt-3 text-xs text-muted-foreground">
                                {{
                                    $t('enrolments.verification_progress', {
                                        checked: checkedCount,
                                        total: verificationFields.length,
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
                            required-ability="verify:class-lists"
                            :next-href="nextVerifyHref"
                            outline
                        />
                        <BaseButton
                            :title="$t('enrolments.button_verify_and_offer')"
                            :disabled="!canVerify"
                            @click="saveVerification"
                        />
                    </div>

                    <p v-if="unlockHelperText" class="text-left text-xs text-muted-foreground">
                        {{ unlockHelperText }}
                    </p>
                </div>
            </div>

            <div class="lg:col-span-3">
                <Sidebar
                    :other-applications="otherApplications"
                    :next-top="nextTop"
                    :type="listType"
                    compact
                />
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

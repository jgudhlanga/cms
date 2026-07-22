import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, successAlert } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import { isValidZimbabweanIdNumber } from '@/lib/zimbabweanId';
import HttpService from '@/services/http.service';
import type { PageProps } from '@/types';
import type { Student } from '@/types/students';
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, type Ref, ref, toValue, watch } from 'vue';

export function useStudentIdNumberCorrection(studentSource: Ref<Student> | (() => Student)) {
    const page = usePage<PageProps>();
    const { isNativeCitizen, formatZimIdNumber } = useUtils();

    const student = computed(() => toValue(studentSource));

    const canEditPersonal = hasAbility('manageOwnStudentPersonalDetails:students');
    const canUpdateStudents = hasAbility('update:students');
    const canManageMaintenance = hasAbility('root:manage');

    const isProfileOwner = computed(() => {
        const authStudentId = page.props.auth?.user?.attributes?.studentId;

        return authStudentId != null && String(authStudentId) === String(student.value?.id);
    });

    const canCorrectIdNumber = computed(
        () => canUpdateStudents || (isProfileOwner.value && canEditPersonal),
    );

    const isZimbabweanId = computed(() => isNativeCitizen(student.value?.attributes?.idType ?? ''));

    const idNumberFixedLocally = ref(false);

    const isIdNumberInvalid = computed(
        () =>
            isZimbabweanId.value
            && student.value?.attributes?.idNumberValid === false
            && !idNumberFixedLocally.value,
    );

    const suggestedIdNumber = computed(() => student.value?.attributes?.suggestedIdNumber ?? null);

    const rectificationStatus = computed(
        () => student.value?.attributes?.idNumberRectificationStatus ?? null,
    );

    const idNumberConflict = computed(() => student.value?.attributes?.idNumberConflict ?? null);

    const isDuplicateConflict = computed(() => rectificationStatus.value === 'duplicate_merge');

    const showInlineEditor = computed(
        () => canCorrectIdNumber.value && !isDuplicateConflict.value,
    );

    const draftIdNumber = ref('');
    const isSavingIdNumber = ref(false);

    watch(
        () => student.value?.attributes?.idNumberValid,
        (valid) => {
            if (valid === true) {
                idNumberFixedLocally.value = true;
            } else if (valid === false) {
                idNumberFixedLocally.value = false;
            }
        },
        { immediate: true },
    );

    watch(
        () => [student.value?.attributes?.idNumber, suggestedIdNumber.value] as const,
        ([idNumber, suggested]) => {
            draftIdNumber.value = suggested ?? idNumber ?? '';
        },
        { immediate: true },
    );

    const onDraftIdNumberUpdate = (value: string | number) => {
        draftIdNumber.value = formatZimIdNumber(String(value)) ?? String(value);
    };

    const useSuggestedIdNumber = () => {
        if (suggestedIdNumber.value) {
            draftIdNumber.value = suggestedIdNumber.value;
        }
    };

    const saveIdNumber = async () => {
        if (isSavingIdNumber.value || !student.value?.id || isDuplicateConflict.value) {
            return;
        }

        const draft = draftIdNumber.value.trim();
        const original = (student.value?.attributes?.idNumber ?? '').trim();

        if (!draft || draft === original) {
            errorAlert(trans('trans.enrollment_invalid_national_id'));

            return;
        }

        if (!isValidZimbabweanIdNumber(draft)) {
            errorAlert(trans('trans.enrollment_invalid_national_id'));

            return;
        }

        isSavingIdNumber.value = true;

        try {
            await HttpService.patch(route('students.id-number.update', student.value.id), {
                id_number: draft,
            });
            idNumberFixedLocally.value = true;
            if (student.value.attributes) {
                student.value.attributes.idNumber = draft;
                student.value.attributes.idNumberValid = true;
                student.value.attributes.suggestedIdNumber = null;
                student.value.attributes.idNumberRectificationStatus = null;
                student.value.attributes.idNumberConflict = null;
            }
            successAlert(trans('trans.maintenance_faulty_data_fix_success'));
            router.visit(window.location.href, {
                replace: true,
                preserveScroll: true,
                preserveState: false,
            });
        } catch (error: unknown) {
            const axiosError = error as {
                response?: {
                    status?: number;
                    data?: { message?: string; errors?: Record<string, string[]> };
                };
            };
            const isConflict = axiosError.response?.status === 409;
            const message = isConflict
                ? trans('trans.id_number_invalid_conflict_it_support')
                : (axiosError.response?.data?.errors?.id_number?.[0]
                    ?? axiosError.response?.data?.message
                    ?? trans('trans.maintenance_faulty_data_fix_failure'));
            errorAlert(message);
        } finally {
            isSavingIdNumber.value = false;
        }
    };

    return {
        canCorrectIdNumber,
        canManageMaintenance,
        draftIdNumber,
        idNumberConflict,
        isDuplicateConflict,
        isIdNumberInvalid,
        isSavingIdNumber,
        isZimbabweanId,
        onDraftIdNumberUpdate,
        rectificationStatus,
        saveIdNumber,
        showInlineEditor,
        suggestedIdNumber,
        useSuggestedIdNumber,
    };
}

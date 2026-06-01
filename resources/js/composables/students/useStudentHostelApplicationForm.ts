import { errorAlert, successAlert } from '@/lib/alerts';
import { jsonApiWriteConfig } from '@/lib/json-api';
import HttpService from '@/services/http.service';
import type { HostelApplicationStudentLookupResponse } from '@/types/hms';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch, type Ref } from 'vue';

export function useStudentHostelApplicationForm(
    studentId: Ref<string>,
    lookup: Ref<HostelApplicationStudentLookupResponse | null>,
    onSaved: () => Promise<void> | void,
) {
    const isSaving = ref(false);
    const saveValidationError = ref<string | null>(null);

    const form = useForm({
        applicationType: 'student' as const,
        studentId: null as number | null,
        studentEnrolmentId: null as number | null,
        nextOfKinName: '',
        nextOfKinContact: '',
        checkIn: '',
        checkOut: '',
    });

    const syncFromLookup = () => {
        const student = lookup.value?.student;
        if (!student) {
            return;
        }

        form.studentId = student.id;
        form.studentEnrolmentId = student.studentEnrolmentId ?? null;
        form.nextOfKinName = student.nextOfKinName ?? '';
        form.nextOfKinContact = student.nextOfKinContact ?? '';
        form.checkIn = lookup.value?.semester?.checkIn ?? '';
        form.checkOut = lookup.value?.semester?.checkOut ?? '';
    };

    watch(lookup, syncFromLookup, { immediate: true, deep: true });

    watch(
        studentId,
        (id) => {
            if (id) {
                form.studentId = Number(id);
            }
        },
        { immediate: true },
    );

    const canSubmit = computed(
        () =>
            (lookup.value?.canApply ?? lookup.value?.canSubmit ?? false)
            && form.nextOfKinName.trim() !== ''
            && form.nextOfKinContact.trim() !== '',
    );

    const submit = async (): Promise<boolean> => {
        saveValidationError.value = null;

        if (!canSubmit.value) {
            saveValidationError.value = trans('hms.next_of_kin_required');
            return false;
        }

        isSaving.value = true;

        try {
            await HttpService.post(
                route('v1.json.hms.hostel-applications.store'),
                {
                    data: {
                        type: 'hostel-applications',
                        attributes: {
                            applicationType: 'student',
                            studentId: form.studentId,
                            studentEnrolmentId: form.studentEnrolmentId,
                            nextOfKinName: form.nextOfKinName,
                            nextOfKinContact: form.nextOfKinContact,
                        },
                    },
                },
                jsonApiWriteConfig(),
            );
            successAlert(trans('hms.application_saved'));
            await onSaved();
            return true;
        } catch {
            errorAlert(trans('hms.application_save_failed'));
            return false;
        } finally {
            isSaving.value = false;
        }
    };

    return {
        form,
        isSaving,
        saveValidationError,
        canSubmit,
        submit,
    };
}

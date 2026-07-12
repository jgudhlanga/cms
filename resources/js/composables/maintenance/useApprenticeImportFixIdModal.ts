import ApprenticeImportFixIdModal from '@/pages/maintenance/partials/apprentices/ApprenticeImportFixIdModal.vue';
import ApprenticeImportMergeIdModal from '@/pages/maintenance/partials/apprentices/ApprenticeImportMergeIdModal.vue';
import { errorAlert, successAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import type { ApprenticeImportPreviewRow } from '@/types/apprentice-import';
import type {
    FixStudentIdConflictResponse,
    StudentAccountMergePreview,
} from '@/types/faulty-student-ids';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { useModal } from 'vue-final-modal';

interface FixStudentIdError {
    response?: {
        status?: number;
        data?: {
            message?: string;
            errors?: Record<string, string[]>;
            conflict?: FixStudentIdConflictResponse;
        };
    };
}

const fetchMergePreview = async (
    studentId: number,
    targetId: number,
    idNumber: string,
): Promise<StudentAccountMergePreview | null> => {
    try {
        const response = (await HttpService.get(
            route('maintenance.faulty-student-ids.merge-preview', studentId),
            {
                params: {
                    target: targetId,
                    id_number: idNumber,
                },
            },
        )) as { data: StudentAccountMergePreview };

        return response.data;
    } catch {
        errorAlert(trans('trans.maintenance_faulty_data_merge_failure'));

        return null;
    }
};

const openMergeModal = (
    preview: StudentAccountMergePreview,
    onMerged: () => void,
): void => {
    const { open, destroy } = useModal({
        defaultModelValue: false,
        keepAlive: false,
        component: ApprenticeImportMergeIdModal,
        attrs: {
            preview,
            onClosed: () => {
                destroy();
            },
            onMerged: () => {
                successAlert(trans('trans.maintenance_faulty_data_merge_success'));
                onMerged();
                destroy();
            },
        },
    });

    void open();
};

export const openApprenticeImportFixIdModal = (
    row: ApprenticeImportPreviewRow,
    onFixed: () => void | Promise<void>,
): void => {
    const saving = ref(false);

    const { open, destroy } = useModal({
        defaultModelValue: false,
        keepAlive: false,
        component: ApprenticeImportFixIdModal,
        attrs: {
            row,
            saving,
            onClosed: () => {
                destroy();
            },
            onSaved: (idNumber: string) => {
                if (!row.studentId) {
                    return;
                }

                saving.value = true;

                void HttpService.patch(route('maintenance.faulty-student-ids.fix', row.studentId), {
                    id_number: idNumber,
                })
                    .then(async () => {
                        successAlert(trans('trans.maintenance_faulty_data_fix_success'));
                        destroy();
                        await onFixed();
                    })
                    .catch(async (error: FixStudentIdError) => {
                        const conflict = error?.response?.data?.conflict;
                        const status = error?.response?.status;

                        if (status === 409 && conflict && row.studentId) {
                            const targetId = conflict.conflictingStudentId ?? conflict.conflicting_student_id;
                            const proposedId = conflict.idNumber ?? conflict.id_number ?? idNumber;

                            if (targetId && proposedId) {
                                const preview = await fetchMergePreview(row.studentId, targetId, proposedId);

                                if (preview) {
                                    destroy();
                                    openMergeModal(preview, () => {
                                        void onFixed();
                                    });
                                }

                                return;
                            }
                        }

                        const messages = error?.response?.data?.errors?.id_number;
                        errorAlert(messages?.[0] ?? trans('trans.maintenance_faulty_data_fix_failure'));
                    })
                    .finally(() => {
                        saving.value = false;
                    });
            },
            onOpenMerge: async (idNumber: string) => {
                if (!row.studentId || !row.idConflict?.conflictingStudentId) {
                    return;
                }

                const preview = await fetchMergePreview(
                    row.studentId,
                    row.idConflict.conflictingStudentId,
                    idNumber,
                );

                if (preview) {
                    destroy();
                    openMergeModal(preview, () => {
                        void onFixed();
                    });
                }
            },
        },
    });

    void open();
};

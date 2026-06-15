import { errorAlert, successAlert } from '@/lib/alerts';
import { firstInertiaErrorMessage } from '@/lib/inertia-errors';
import { useModalStore } from '@/store/core/useModalStore';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import type { ComputedRef, Ref } from 'vue';

export const MOVE_SYLLABUS_MODULES_MODAL = 'course_syllabus_move_modules';

export function useCourseSyllabusModuleMove(
    moveModulesUrl: ComputedRef<string>,
    moveTargetOptions: Ref<SelectOption[]>,
    selectedModuleIds: Ref<number[]>,
    onSuccess?: () => void,
) {
    const { openModal, closeModal } = useModalStore();

    const moveForm = useForm({
        course_syllabus_module_ids: [] as number[],
        target_academic_year_option_id: null as number | null,
    });

    const openMoveModulesModal = (): void => {
        if (moveTargetOptions.value.length === 0) {
            return;
        }
        moveForm.course_syllabus_module_ids = [...selectedModuleIds.value];
        moveForm.target_academic_year_option_id = Number(moveTargetOptions.value[0]?.value ?? null) || null;
        moveForm.clearErrors();
        openModal(MOVE_SYLLABUS_MODULES_MODAL);
    };

    const submitMoveModules = (): void => {
        moveForm.post(moveModulesUrl.value, {
            preserveScroll: true,
            onSuccess: () => {
                successAlert(trans('syllabus.move_modules_success'));
                closeModal(MOVE_SYLLABUS_MODULES_MODAL);
                selectedModuleIds.value = [];
                onSuccess?.();
            },
            onError: (errors) => {
                errorAlert(firstInertiaErrorMessage(errors, trans('syllabus.move_modules_failed')));
            },
        });
    };

    const resetMoveFormOnModalClose = (): void => {
        moveForm.clearErrors();
    };

    return {
        moveForm,
        openMoveModulesModal,
        submitMoveModules,
        resetMoveFormOnModalClose,
    };
}

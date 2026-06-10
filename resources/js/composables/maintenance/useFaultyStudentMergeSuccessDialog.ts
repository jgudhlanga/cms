import FaultyStudentMergeSuccessDialog from '@/pages/maintenance/partials/FaultyStudentMergeSuccessDialog.vue';
import type { FaultyStudentMergeResult } from '@/types/faulty-student-ids';
import { router } from '@inertiajs/vue3';
import { useModal } from 'vue-final-modal';

export const openFaultyStudentMergeSuccessDialog = (result: FaultyStudentMergeResult): void => {
    const { open, destroy } = useModal({
        defaultModelValue: false,
        keepAlive: false,
        component: FaultyStudentMergeSuccessDialog,
        attrs: {
            result,
            onClosed: () => {
                destroy();
            },
            onViewProfile: () => {
                router.visit(route('students.profile', String(result.userId)));
                destroy();
            },
        },
    });

    void open();
};

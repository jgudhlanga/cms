import FaultyStudentMergeSuccessDialog from '@/pages/maintenance/partials/students/FaultyStudentMergeSuccessDialog.vue';
import type { FaultyStudentMergeResult } from '@/types/faulty-student-ids';
import { buildStudentShowUrl } from '@/lib/studentShowNavigation';
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
                router.visit(
                    buildStudentShowUrl(result.studentId, {
                        from: 'maintenance',
                        return: route('maintenance.faulty-student-ids'),
                    }),
                );
                destroy();
            },
        },
    });

    void open();
};

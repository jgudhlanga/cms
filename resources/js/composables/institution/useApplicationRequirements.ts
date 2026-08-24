import { errorAlert, successAlert } from '@/lib/alerts';
import { toggleFormLoader } from '@/lib/forms';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useApplicationRequirements = () => {
    const { levelRequirementsFormSchema } = useDepartmentLevels(false);
    const { courserRequirementsFormSchema } = useDepartmentCourses(false);

    const storeLevelRequirements = (
        institutionDepartmentId: string,
        departmentLevelId: string,
        form: InertiaForm<any>,
    ) => {
        const success = trans('application_requirements.saved');
        const error = trans('trans.item_save_failure', { item: trans_choice('trans.level', 1) });

        form.post(
            route('application-requirements.level.store', {
                institution_department: institutionDepartmentId,
                department_level: departmentLevelId,
            }),
            {
                onStart: () => toggleFormLoader(true),
                onFinish: () => {
                    form.reset();
                    toggleFormLoader(false);
                },
                onSuccess: () => successAlert(success),
                onError: () => errorAlert(error),
            },
        );
    };

    const storeCourseRequirements = (
        institutionDepartmentId: string,
        departmentCourseId: string,
        form: InertiaForm<any>,
    ) => {
        const success = trans('application_requirements.saved');
        const error = trans('trans.item_save_failure', { item: trans_choice('trans.course', 1) });

        form.post(
            route('application-requirements.course.store', {
                institution_department: institutionDepartmentId,
                department_course: departmentCourseId,
            }),
            {
                onStart: () => toggleFormLoader(true),
                onFinish: () => {
                    form.reset();
                    toggleFormLoader(false);
                },
                onSuccess: () => successAlert(success),
                onError: () => errorAlert(error),
            },
        );
    };

    return {
        storeLevelRequirements,
        storeCourseRequirements,
        levelRequirementsFormSchema,
        courserRequirementsFormSchema,
    };
};

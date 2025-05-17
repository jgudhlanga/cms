import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { DepartmentCourse, DepartmentCourseLevel } from '@/types/department-meta-data';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useDepartmentCourses = () => {
    const { moreActionButton, textLink, checkStatusIcon, onEdit } = useDataTables();
    const { props } = usePage();
    const { can } = props?.auth as Auth;
    const createDepartmentCourseColumns = () => {
        return [
            {
                header: trans_choice('trans.course', 1),
                accessorKey: 'course',
                cell: ({ row }: { row: { original: DepartmentCourse } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return textLink(route('department-courses.show', id), row.original.attributes?.course);
                },
            },
            {
                header: trans_choice('trans.level', 2),
                accessorKey: 'levels',
                cell: ({ row }: { row: { original: DepartmentCourse } }) => {
                    return  row.original.relationships?.departmentCourseLevels
                        ?.map((item: DepartmentCourseLevel) => item?.level)
                        ?.join(", ");
                },
            },
            {
                header: trans('trans.show_on_current_application_period'),
                accessorKey: 'showOnCurrentApplicationPeriod',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: DepartmentCourse } }) => {
                    return checkStatusIcon(row.original.attributes?.showOnCurrentApplicationPeriod);
                },
            },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: DepartmentCourse } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'edit',
                            action: () => onEdit(can['update:department-metadata'], route('department-courses.show', id)),
                        },
                        {
                            key: 'archive',
                            action: () => {},
                        },
                        {
                            key: 'restore',
                            action: () => {},
                        },
                        {
                            key: 'delete',
                            action: () => {},
                        },
                    ]);
                },
            },
        ];
    };

    const syncDepartmentCourses = (institutionDepartmentId: string, form: InertiaForm<any>) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.course', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.course', 2) });
            form.post(
                route('department-courses.sync', institutionDepartmentId),
                buildFormOptions(form, success, error, APP_MODULE_KEYS.department_courses),
            );
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const updateDepartmentCourses = (departmentCourseId: string, form: InertiaForm<any>) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.course', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.course', 2) });
            form.post(route('department-courses.update', departmentCourseId), buildFormOptions(form, success, error));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const openDepartmentCoursesModal = (departmentCourses: Array<string | undefined | null> | null) => {
        if (!can['create:department-metadata']) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.department_courses, edit: departmentCourses });
    };

    return {
        createDepartmentCourseColumns,
        openDepartmentCoursesModal,
        syncDepartmentCourses,
        updateDepartmentCourses,
    };
};

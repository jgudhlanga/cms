import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, toggleFormLoader } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { Auth } from '@/types';
import { DepartmentCourse, DepartmentCourseLevel, DepartmentCourseMetaData } from '@/types/department-meta-data';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useDepartmentCourses = () => {
    const { moreActionButton, textLink, checkStatusIcon, onEdit } = useDataTables();
    const { props } = usePage();
    const { can } = props?.auth as Auth;
    const { navigateTo } = useUtils();
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
                    return row.original.relationships?.departmentCourseLevels?.map((item: DepartmentCourseLevel) => item?.level)?.join(', ');
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

    const updateDepartmentCourses = (departmentCourseId: string, form: InertiaForm<any>, institutionDepartmentId: string) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.course', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.course', 2) });
            form.post(route('department-courses.update', departmentCourseId), {
                onStart: () => toggleFormLoader(true),
                onFinish: () => {
                    form.reset();
                    toggleFormLoader(false);
                },
                onSuccess: () => {
                    successAlert(success);
                    navigateTo(route('institution-departments.show', getIdParams(institutionDepartmentId)));
                },
                onError: () => {
                    errorAlert(error);
                },
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const openDepartmentCoursesModal = (departmentCourses: Array<string | undefined | null> | null) => {
        if (!can['create:department-metadata']) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.department_courses, edit: departmentCourses });
    };

    const isLoading = ref(false);
    const departmentCoursesMetaData = ref<DepartmentCourseMetaData | null>(null);

    const loadDepartmentCoursesMetaData = async (institutionDepartmentId: string) => {
        try {
            isLoading.value = true;
            departmentCoursesMetaData.value = await HttpService.get(route('v1.department-metadata.courses', institutionDepartmentId));
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.course', 2) }));
        } finally {
            isLoading.value = false;
        }
    };
    return {
        createDepartmentCourseColumns,
        openDepartmentCoursesModal,
        syncDepartmentCourses,
        updateDepartmentCourses,
        isLoading,
        departmentCoursesMetaData,
        loadDepartmentCoursesMetaData,
    };
};

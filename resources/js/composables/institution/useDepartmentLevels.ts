import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { Auth } from '@/types';
import { DepartmentLevel, DepartmentLevelCourse, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useDepartmentLevels = () => {
    const { moreActionButton, textLink, actionButton } = useDataTables();
    const { props } = usePage();
    const { can } = props?.auth as Auth;
    const { navigateTo } = useUtils();
    const createDepartmentLevelColumns = () => {
        return [
            {
                header: trans_choice('trans.level', 1),
                accessorKey: 'level',
                cell: ({ row }: { row: { original: DepartmentLevel } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return textLink(route('department-levels.requirements', id), row.original.attributes?.level);
                },
            },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.requirement', 2),
                accessorKey: 'requirements',
                enableSorting: false,
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: DepartmentLevel } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return actionButton({
                        title: trans_choice('trans.requirement', 2),
                        variant: ColorVariant.primary_outline,
                        onClick: () => navigateTo(route('department-levels.requirements', id)),
                    });
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: DepartmentLevel } }) => {
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'view',
                            action: () => {},
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

    const syncDepartmentLevels = (institutionDepartmentId: string, form: InertiaForm<any>) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.level', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.level', 2) });
            form.post(
                route('department-levels.sync', institutionDepartmentId),
                buildFormOptions(form, success, error, APP_MODULE_KEYS.department_levels),
            );
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const storeDepartmentLevelRequirements = (departmentLevelId: string, form: InertiaForm<any>) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.level', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.level', 2) });
            form.post(route('department-levels.store-requirements', departmentLevelId), buildFormOptions(form, success, error));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const openDepartmentLevelsModal = (departmentLevels: Array<string | undefined | null> | null) => {
        if (!can['create:department-metadata']) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.department_levels, edit: departmentLevels });
    };

    const departmentLevels = ref<DepartmentLevel[]>([]);
    const isLoading = ref(false);

    const listDepartmentLevels = async (institutionDepartmentId: string) => {
        isLoading.value = true;
        departmentLevels.value = await HttpService.get(`api/v1/institution-departments/${institutionDepartmentId}/levels`);
        isLoading.value = false;
    };

    const levelCourses = ref<DepartmentLevelCourse[]>([]);

    const listLevelCourses = async (departmentLevelId: string) => {
        isLoading.value = true;
        levelCourses.value = await HttpService.get(`api/v1/institution-departments/levels/${departmentLevelId}/courses`);
        isLoading.value = false;
    };

    const levelRequirements =  ref<DepartmentLevelRequirement[]>([]);
    const listLevelRequirements = async (departmentLevelId: string) => {
        isLoading.value = true;
        levelRequirements.value = await HttpService.get(`api/v1/institution-departments/levels/${departmentLevelId}/requirements`);
        isLoading.value = false;
    };

    return {
        createDepartmentLevelColumns,
        openDepartmentLevelsModal,
        syncDepartmentLevels,
        storeDepartmentLevelRequirements,
        listDepartmentLevels,
        isLoading,
        departmentLevels,
        listLevelCourses,
        levelCourses,
        levelRequirements,
        listLevelRequirements,
    };
};

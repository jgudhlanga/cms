import { useDataTables } from '@/composables/core/useDataTables';
import { ColorVariant } from '@/enums/colors';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { DepartmentLevel, DepartmentLevelCourse } from '@/types/department-meta-data';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { useUtils } from '@/composables/core/useUtils';
import { ref } from 'vue';
import { useDropdowns } from '@/composables/core/useDropdowns';

export const useDepartmentLevels = () => {
    const { moreActionButton, textLink, actionButton } = useDataTables();
    const { props } = usePage();
    const { can } = props?.auth as Auth;
    const {navigateTo} = useUtils()
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
            form.post(
                route('department-levels.store-requirements', departmentLevelId),
                buildFormOptions(form, success, error),
            );
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
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({
            url: `api/v1/institution-departments/${institutionDepartmentId}/levels`,
            transChoiceKey: 'trans.level',
        });
        isLoading.value = false;
        departmentLevels.value = data.value;
    };

    const levelCourses = ref<DepartmentLevelCourse[]>([]);

    const listLevelCourses = async (institutionDepartmentId: string, departmentLevelId: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({
            url: `api/v1/institution-departments/${institutionDepartmentId}/levels/${departmentLevelId}/courses`,
            transChoiceKey: 'trans.level',
        });
        isLoading.value = false;
        levelCourses.value = data.value;
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
    };
};

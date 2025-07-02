import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, toggleFormLoader } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { Auth } from '@/types';
import {
    DepartmentLevel,
    DepartmentLevelCourse,
    DepartmentLevelMetaData,
    DepartmentLevelRequirement
} from '@/types/department-meta-data';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { ref } from 'vue';
import { z } from 'zod';

export const useDepartmentLevels = () => {
    const { moreActionButton, textLink, actionButton, checkStatusIcon, onEdit } = useDataTables();
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
            {
                header: trans('trans.configured'),
                accessorKey: 'requirement',
                meta: { align: 'center' },
                enableSorting: false,
                cell: ({ row }: { row: { original: DepartmentLevel } }) => {
                    return checkStatusIcon(!!row.original.relationships?.requirement);
                },
            },
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
                    const allowed = hasAbility('create:department-metadata');
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'edit',
                            action: () => onEdit(allowed, route('department-levels.requirements', id)),
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

    const storeDepartmentLevelRequirements = (departmentLevelId: string, form: InertiaForm<any>, institutionDepartmentId: string) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.level', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.level', 2) });
            form.post(route('department-levels.store-requirements', departmentLevelId), {
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

    const {
        levelRequirements: storeLevelRequirements,
        o_level_subject_ids,
        required_level_completed,
        read_write_acknowledged,
    } = storeToRefs(useCreateApplicationFormStore());

    const levelRequirements = ref<DepartmentLevelRequirement | null>(storeLevelRequirements?.value ?? null);

    const listLevelRequirements = async (departmentLevelId: string) => {
        if (Number(departmentLevelId) > 0) {
            isLoading.value = true;
            levelRequirements.value = await HttpService.get(`api/v1/institution-departments/levels/${departmentLevelId}/requirements`);
            storeLevelRequirements!.value = levelRequirements.value;
            o_level_subject_ids!.value = null;
            required_level_completed!.value = null;
            read_write_acknowledged!.value = null;
            isLoading.value = false;
        }
    };

    const levelRequirementsFormSchema = (isOLevelRequired: boolean) =>
        z.object({
            required_subjects_count: isOLevelRequired
                ? z
                      .string()
                      .nonempty(trans('trans.enter_required_field', { field: trans('trans.required_subjects_count') }))
                      .refine((val) => !isNaN(Number(val)), {
                          message: trans('trans.field_must_be_number', { field: trans('trans.required_subjects_count') }),
                      })
                : z.string().optional(),
            main_subjects_count: isOLevelRequired
                ? z
                      .string()
                      .nonempty(trans('trans.enter_required_field', { field: trans('trans.main_subjects_count') }))
                      .refine((val) => !isNaN(Number(val)), {
                          message: trans('trans.field_must_be_number', { field: trans('trans.main_subjects_count') }),
                      })
                : z.string().optional(),
            other_subjects_count: isOLevelRequired
                ? z
                      .string()
                      .nonempty(trans('trans.enter_required_field', { field: trans('trans.other_subjects_count') }))
                      .refine((val) => !isNaN(Number(val)), {
                          message: trans('trans.field_must_be_number', { field: trans('trans.other_subjects_count') }),
                      })
                : z.string().optional(),
        });

    const departmentLevelsMetadata = ref<DepartmentLevelMetaData | null>(null);

    const loadDepartmentLevelsMetadata = async (institutionDepartmentId: string) => {
        try {
            isLoading.value = true;
            departmentLevelsMetadata.value = await HttpService.get(route('v1.department-metadata.levels', institutionDepartmentId));
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.levels', 2) }));
        } finally {
            isLoading.value = false;
        }
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
        levelRequirementsFormSchema,
        departmentLevelsMetadata,
        loadDepartmentLevelsMetadata,
    };
};

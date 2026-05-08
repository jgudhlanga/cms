import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { AssessmentType } from '@/types/institution';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useAssessmentTypes = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();

    const createAssessmentTypeColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;

        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.mode_of_study', 2), accessorKey: 'attributes.modesOfStudy' },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: AssessmentType } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.assessment_type', 1);

                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:institution-settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:institution-settings'], route('assessment-types.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:institution-settings'], route('assessment-types.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:institution-settings'], route('assessment-types.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [{ transChoiceKey: 'settings', href: route('settings.index') }, { transChoiceKey: 'assessment_type' }];

    const saveAssessmentType = (form: InertiaForm<any>, assessmentType?: AssessmentType) => {
        const success = trans('trans.item_saved', { item: trans_choice('trans.assessment_type', 1) });
        const error = trans('trans.item_save_failure', { item: trans_choice('trans.assessment_type', 1) });

        if (assessmentType) {
            const id = getIdParams(assessmentType.id?.toString() ?? '');
            form.put(route('assessment-types.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.assessment_types));
        } else {
            form.post(route('assessment-types.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.assessment_types));
        }
    };

    const onOpenModal = (can: boolean, assessmentType?: AssessmentType) => {
        if (!can) {
            return forbiddenAlert();
        }

        openModal({ name: APP_MODULE_KEYS.assessment_types, edit: assessmentType });
    };

    return {
        createAssessmentTypeColumns,
        breadcrumbs,
        saveAssessmentType,
        onOpenModal,
    };
};

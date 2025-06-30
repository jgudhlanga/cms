import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { ApplicationStep } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useApplicationSteps = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const createApplicationStepColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('#', 1), accessorKey: 'attributes.position', meta: {align: 'left'} },
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: ApplicationStep } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.name', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:settings'], route('application-steps.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:settings'], route('application-steps.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:settings'], route('application-steps.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [
        {
            transChoiceKey: 'settings',
            href: route('settings.index'),
        },
        { transChoiceKey: 'application_step' },
    ];

    const saveApplicationStep = (form: InertiaForm<any>, applicationStep?: ApplicationStep) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.applicationStep', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.applicationStep', 1) });
            if (applicationStep) {
                const id = getIdParams(applicationStep.id?.toString() ?? '');
                form.put(route('application-steps.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.application_steps));
            } else {
                form.post(route('application-steps.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.application_steps));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, applicationStep?: ApplicationStep) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.application_steps, edit: applicationStep });
    };

    return {
        createApplicationStepColumns,
        breadcrumbs,
        onOpenModal,
        saveApplicationStep,
    };
};

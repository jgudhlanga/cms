import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { ModeOfStudy } from '@/types/institution';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useModeOfStudy = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const createModeOfStudyColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: ModeOfStudy } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.mode_of_study', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:institution-settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:institution-settings'], route('mode-of-studies.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:institution-settings'], route('mode-of-studies.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:institution-settings'], route('mode-of-studies.force-delete', id), name),
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
        { transChoiceKey: 'mode_of_study' },
    ];

    const saveModeOfStudy = (form: InertiaForm<any>, modeOfStudy?: ModeOfStudy) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.mode_of_study', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.mode_of_study', 1) });
            if (modeOfStudy) {
                const id = getIdParams(modeOfStudy.id?.toString() ?? '');
                form.put(route('mode-of-studies.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.modes_of_study));
            } else {
                form.post(route('mode-of-studies.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.modes_of_study));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, modeOfStudy?: ModeOfStudy) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.modes_of_study, edit: modeOfStudy });
    };

    return {
        createModeOfStudyColumns,
        breadcrumbs,
        onOpenModal,
        saveModeOfStudy,
    };
};

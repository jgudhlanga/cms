import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { errorAlert, forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Switch } from '@/components/ui/switch';
import { Auth } from '@/types';
import { Module } from '@/types/rbac';
import type { Link } from '@/types/ui';
import { InertiaForm, router, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h } from 'vue';

const DASHBOARD_TAB_OPTIONS = [
    { key: 'overview', labelKey: 'dashboard.overview' },
    { key: 'academic', labelKey: 'trans.academic' },
    { key: 'enrolments', labelKey: 'trans.enrolment', choice: 2 },
    { key: 'attendance', labelKey: 'dashboard.attendance' },
    { key: 'staff', labelKey: 'trans.staff' },
    { key: 'finance', labelKey: 'trans.finance', choice: 2 },
    { key: 'hostel', labelKey: 'dashboard.hostel' },
] as const;

export const useModules = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, textLink } = useDataTables();
    const { titleSchema } = useSharedFormSchema();

    const saveModule = (form: InertiaForm<any>, module?: Module) => {
        try {
            titleSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.module', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.module', 1) });
            if (module) {
                const id = getIdParams(module.id?.toString() ?? '');
                form.put(route('modules.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.modules));
            } else {
                form.post(route('modules.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.modules));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const saveModuleSettings = (form: InertiaForm<any>, module: Module) => {
        const id = getIdParams(module.id?.toString() ?? '');
        const success = trans('trans.item_saved', { item: trans_choice('trans.module', 1) });
        const error = trans('trans.item_save_failure', { item: trans_choice('trans.module', 1) });

        form.put(route('modules.settings', id), {
            ...buildFormOptions(form, success, error),
            only: ['module', 'moduleState'],
        });
    };

    const toggleModuleStatus = (module: Module, status: boolean, canUpdate: boolean) => {
        if (!canUpdate) {
            return forbiddenAlert();
        }

        const id = getIdParams(module.id?.toString() ?? '');
        const error = trans('trans.item_save_failure', { item: trans_choice('trans.module', 1) });

        router.put(
            route('modules.update-status', id),
            { status },
            {
                preserveScroll: true,
                only: ['modules', 'moduleState'],
                onError: () => errorAlert(error),
            },
        );
    };

    const createModuleColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            {
                header: trans_choice('trans.title', 1),
                accessorKey: 'attributes.title',
                cell: ({ row }: { row: { original: Module } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const canView = can['view:modules'] || can['viewAny:modules'];

                    if (!canView) {
                        return row.original.attributes.title;
                    }

                    return textLink(route('modules.show', id), row.original.attributes.title);
                },
            },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans('trans.enabled'),
                accessorKey: 'attributes.status',
                enableSorting: false,
                cell: ({ row }: { row: { original: Module } }) => {
                    const module = row.original;
                    const isTrashed = !!module.attributes?.deletedAt;

                    return h(Switch, {
                        modelValue: !!module.attributes?.status,
                        disabled: !can['update:modules'] || isTrashed,
                        'onUpdate:modelValue': (value: boolean) => toggleModuleStatus(module, value, !!can['update:modules']),
                    });
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Module } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.module', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:modules'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:modules'], route('modules.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:modules'], route('modules.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:modules'], route('modules.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [{ transKey: 'trans.rbac', href: route('rbac.index') }, { transChoiceKey: 'module' }];

    const showBreadcrumbs = (module: Module): Array<Link> => [
        { transKey: 'trans.rbac', href: route('rbac.index') },
        { transChoiceKey: 'module', href: route('modules.index') },
        { title: module.attributes.title },
    ];

    const onOpenModal = (can: boolean, edit?: Module) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.modules, edit: edit });
    };

    return {
        createModuleColumns,
        breadcrumbs,
        showBreadcrumbs,
        onOpenModal,
        saveModule,
        saveModuleSettings,
        dashboardTabOptions: DASHBOARD_TAB_OPTIONS,
    };
};

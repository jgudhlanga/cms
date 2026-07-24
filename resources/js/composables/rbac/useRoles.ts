import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { errorAlert, forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { type Role } from '@/types/rbac';
import { ApiFilterResponse } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { InertiaForm, router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useRoles = () => {
    const { nameSchema } = useSharedFormSchema();

    const getName = () => trans_choice('trans.role', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const saveRole = (form: InertiaForm<any>, role?: Role) => {
        try {
            nameSchema().parse(form);
            if (role) {
                const id = getIdParams(role.id?.toString() ?? '');
                form.put(route('roles.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.roles));
            } else {
                form.post(route('roles.store'), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.roles));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const { moreActionButton, countActionButton, onDelete, onRestore, onForceDelete, onView } = useDataTables();
    const createRoleColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.role_group', 1), accessorKey: 'attributes.roleGroup' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.permission', 2),
                accessorKey: 'permissions',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: Role } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return countActionButton({
                        title: row.original?.attributes?.permissionsCount?.toString() ?? '0',
                        onClick: () => (hasAbility('view:roles') ? router.get(route('roles.show', id)) : forbiddenAlert()),
                    });
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Role } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:roles'), row.original) },
                        { key: 'view', action: () => onView(hasAbility('view:roles'), route('roles.show', id)) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:roles'), route('roles.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:roles'), route('roles.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(hasAbility('forceDelete:roles'), route('roles.force-delete', id), getName()),
                        },
                    ]);
                },
            },
        ];
    };
    const indexBreadcrumbs: Array<Link> = [
        {
            transKey: 'trans.rbac',
            href: route('rbac.index'),
        },
        { transChoiceKey: 'role' },
    ];

    const onOpenModal = (can: boolean, role?: Role) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.roles, edit: role });
    };

    const isLoading = ref(false);
    const roles = ref<ApiFilterResponse | null>(null);

    const listRoles = async (url: string) => {
        try {
            isLoading.value = true;
            roles.value = await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.role', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        createRoleColumns,
        indexBreadcrumbs,
        onOpenModal,
        saveRole,
        listRoles,
        roles,
        isLoading,
    };
};

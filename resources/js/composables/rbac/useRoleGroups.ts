import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { RoleGroup } from '@/types/rbac';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useRoleGroups = () => {
    const { nameSchema } = useSharedFormSchema();

    const getName = () => trans_choice('trans.role_group', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const saveRoleGroup = (form: InertiaForm<any>, roleGroup?: RoleGroup) => {
        try {
            nameSchema().parse(form);
            if (roleGroup) {
                const id = getIdParams(roleGroup.id?.toString() ?? '');
                form.put(route('role-groups.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.role_groups));
            } else {
                form.post(route('role-groups.store'), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.role_groups));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const { moreActionButton, onDelete, onRestore, onForceDelete } = useDataTables();
    const createRoleGroupColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: RoleGroup } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:settings'), row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:settings'), route('role-groups.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:settings'), route('role-groups.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(hasAbility('forceDelete:settings'), route('role-groups.force-delete', id), getName()),
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
        { transChoiceKey: 'role_group' },
    ];

    const onOpenModal = (can: boolean, roleGroup?: RoleGroup) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.role_groups, edit: roleGroup });
    };

    const isLoading = ref(false);
    const roleGroups = ref<RoleGroup[]>([]);

    const listRoleGroups = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: 'api/v1/rbac/role-groups?page_size=100', search, transChoiceKey: 'trans.role_group' });
        isLoading.value = false;
        roleGroups.value = data.value;
    };
    return {
        createRoleGroupColumns,
        indexBreadcrumbs,
        onOpenModal,
        saveRoleGroup,
        listRoleGroups,
        roleGroups,
        isLoading,
    };
};

import { useDataTables } from '@/composables/core/useDataTables';
import { openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Permission, Role } from '@/types/acl';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useRolePermissions = (role?: Role) => {
    const { checkStatusIcon } = useDataTables();
    const groupPermissionsByModule = (permissions: Array<Permission> | null | undefined) => {
        return permissions?.reduce((group: { [key: string]: Permission[] }, item: any) => {
            if (!group[item?.relationships?.module?.attributes?.title]) {
                group[item?.relationships?.module?.attributes?.title] = [];
            }
            group[item?.relationships?.module?.attributes?.title].push(item);
            return group;
        }, {});
    };

    const isAllocated = (permission: Permission): boolean | undefined => {
        return role?.relationships?.permissions?.some((perm: Permission) => perm.id == permission.id) || false;
    };

    const createRolePermissionColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            {
                header: trans_choice('trans.module', 1),
                accessorKey: 'module',
                cell: ({ row }: { row: { original: Permission } }) => row.original?.relationships?.module?.attributes?.title,
            },
            {
                header: trans('trans.guard_name'),
                accessorKey: 'guardName',
                cell: ({ row }: { row: { original: Permission } }) => row.original?.attributes?.guardName,
            },
            {
                header: trans('trans.assigned'),
                accessorKey: 'assigned',
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Permission } }) => {
                    const allocated = isAllocated(row.original);
                    return checkStatusIcon(allocated);
                },
            },
        ];
    };

    const onOpenModal = (role?: Role, permissions?: Array<Permission> | null | undefined) => {
        openModal({ name: APP_MODULE_KEYS.role_permissions, edit: [role, permissions] });
    };

    const showBreadcrumbs: Array<Link> = [
        { transChoiceKey: 'settings', href: route('settings.index') },
        { transChoiceKey: 'role', href: route('roles.index') },
    ];

    const saveRolePermissions = (role: Role, form: InertiaForm<any>) => {
        const success = trans('trans.role_permissions_update');
        const error = trans('trans.role_permissions_update_failure');
        form.put(
            route('roles.sync-permissions', getIdParams(role.id?.toString() as string)),
            buildFormOptions(form, success, error, APP_MODULE_KEYS.role_permissions),
        );
    };

    return {
        groupPermissionsByModule,
        createRolePermissionColumns,
        onOpenModal,
        showBreadcrumbs,
        saveRolePermissions,
    };
};

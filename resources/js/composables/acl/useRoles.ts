import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { type Role } from '@/types/acl';
import type { Link } from '@/types/ui';
import { InertiaForm, router, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useRoles = () => {
    const { nameSchema } = useSharedFormSchema();
    const saveRole = (form: InertiaForm<any>, role?: Role) => {
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.role', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.role', 1) });
            if (role) {
                const id = getIdParams(role.id?.toString() ?? '');
                form.put(route('roles.create', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.roles));
            } else {
                form.post(route('roles.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.roles));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const { moreActionButton, countActionButton, onDelete, onRestore, onForceDelete, onView } = useDataTables();
    const createRoleColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.guard_name'), accessorKey: 'attributes.guardName' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.permission', 2),
                accessorKey: 'permissions',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: Role } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return countActionButton({
                        title: row.original?.attributes?.permissionsCount?.toString() ?? '0',
                        onClick: () => (can['view:roles'] ? router.get(route('roles.show', id)) : forbiddenAlert()),
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
                    const name = trans_choice('trans.role', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:roles'], row.original) },
                        { key: 'view', action: () => onView(can['view:roles'], route('roles.show', id)) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:roles'], route('roles.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:roles'], route('roles.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:roles'], route('roles.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };
    const indexBreadcrumbs: Array<Link> = [
        {
            transChoiceKey: 'settings',
            href: route('settings.index'),
        },
        { transChoiceKey: 'role' },
    ];

    const onOpenModal = (can: boolean, role?: Role) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.roles, edit: role });
    };

    const isLoading = ref(false);
    const roles = ref<Role[]>([]);

    const listRoles = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: 'api/v1/acl/roles?page_size=100', search, transChoiceKey: 'trans.role' });
        isLoading.value = false;
        roles.value = data.value;
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

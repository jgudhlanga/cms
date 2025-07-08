import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { useUtils } from '@/composables/core/useUtils';
import { errorAlert } from '@/lib/alerts';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { Auth } from '@/types';
import { ApiFilterResponse } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { User } from '@/types/users';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useUsers = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, avatar, onView } = useDataTables();
    const createUserColumns = () => {
        const { props } = usePage();
        const { formatDate } = useUtils();
        const { can } = props?.auth as Auth;
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: User } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return avatar({
                        href: route('users.show', id),
                        title: `${row.original.attributes?.name ?? ''}`,
                        src: row.original.attributes?.avatarUrl ?? '',
                        classes: 'size-8 rounded-full',
                    });
                },
            },
            { header: trans('trans.email_address'), accessorKey: 'attributes.email' },
            { header: trans('trans.phone_number'), accessorKey: 'attributes.phoneNumber' },
            { header: trans_choice('trans.status', 1), accessorKey: 'attributes.status' },
            { header: trans('trans.login_count'), accessorKey: 'attributes.loginCount', meta: { align: 'center' } },
            {
                header: trans('trans.last_login'),
                accessorKey: 'lastLoginAt',
                cell: ({ row }: { row: { original: User } }) => {
                    const loginDate = row.original?.attributes?.lastLoginAt ?? '';
                    return loginDate ? formatDate(loginDate, 'LLLL') : '---';
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: User } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.user', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'view',
                            action: () => onView(can['view:users'], route('users.show', id)),
                        },
                        {
                            key: 'edit',
                            action: () => {},
                        },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:users'], route('users.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:users'], route('users.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:users'], route('users.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [{ transChoiceKey: 'user' }];

    const saveUser = (form: InertiaForm<any>, user?: User) => {
        const { titleSchema } = useSharedFormSchema();
        try {
            titleSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.user', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.user', 1) });
            if (user) {
                const id = getIdParams(user.id?.toString() ?? '');
                form.put(route('users.update', id), buildFormOptions(form, success, error));
            } else {
                form.post(route('users.store'), buildFormOptions(form, success, error));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const isLoading = ref(false);
    const users = ref<ApiFilterResponse | null>(null);
    const loadUsers = async (url: string) => {
        try {
            isLoading.value = true;
            users.value = await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.user', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        breadcrumbs,
        createUserColumns,
        saveUser,
        loadUsers,
        users,
        isLoading,
    };
};

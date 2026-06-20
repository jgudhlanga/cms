import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Gender } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useGenders = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const genders = ref<Gender[]>([]);
    const { titleSchema } = useSharedFormSchema();
    const createGenderColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Gender } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.gender', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:settings'], route('genders.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:settings'], route('genders.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:settings'], route('genders.force-delete', id), name),
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
        { transChoiceKey: 'gender' },
    ];

    const saveGender = (form: InertiaForm<any>, gender?: Gender) => {
        try {
            titleSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.gender', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.gender', 1) });
            if (gender) {
                const id = getIdParams(gender.id?.toString() ?? '');
                form.put(route('genders.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.genders));
            } else {
                form.post(route('genders.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.genders));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const onOpenModal = (can: boolean, gender?: Gender) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.genders, edit: gender });
    };

    const listGenders = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: route('v1.genders.index'), search, transChoiceKey: 'trans.gender' });
        isLoading.value = false;
        genders.value = data.value;
    };

    return {
        createGenderColumns,
        breadcrumbs,
        onOpenModal,
        saveGender,
        isLoading,
        genders,
        listGenders,
    };
};

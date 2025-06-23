import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { SponsorType } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useSponsorTypes = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const sponsorTypes = ref<SponsorType[]>([]);
    const createSponsorTypeColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: SponsorType } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.name', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:settings'], route('sponsor-types.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:settings'], route('sponsor-types.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:settings'], route('sponsor-types.force-delete', id), name),
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
        { transChoiceKey: 'sponsor_type' },
    ];

    const saveSponsorType = (form: InertiaForm<any>, sponsorType?: SponsorType) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.sponsor_type', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.sponsor_type', 1) });
            if (sponsorType) {
                const id = getIdParams(sponsorType.id?.toString() ?? '');
                form.put(route('sponsor-types.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.sponsor_types));
            } else {
                form.post(route('sponsor-types.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.sponsor_types));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, sponsorType?: SponsorType) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.sponsor_types, edit: sponsorType });
    };

    const listSponsorTypes = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: route('v1.sponsor-types.index'), search, transChoiceKey: 'trans.sponsor_type' });
        isLoading.value = false;
        sponsorTypes.value = data.value;
    };

    return {
        createSponsorTypeColumns,
        breadcrumbs,
        onOpenModal,
        saveSponsorType,
        isLoading,
        sponsorTypes,
        listSponsorTypes,
    };
};

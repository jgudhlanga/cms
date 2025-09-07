import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { DocumentType } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useDocumentTypes = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const documentTypes = ref<DocumentType[]>([]);
    const getName = () => trans_choice('trans.document_type', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const createDocumentTypeColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: DocumentType } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');

                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:settings'), row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:settings'), route('document-types.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:settings'), route('document-types.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(hasAbility('forceDelete:settings'), route('document-types.force-delete', id), getName()),
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
        { transChoiceKey: 'document_type' },
    ];

    const saveDocumentType = (form: InertiaForm<any>, documentType?: DocumentType) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            if (documentType) {
                const id = getIdParams(documentType.id?.toString() ?? '');
                form.put(route('document-types.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.document_types));
            } else {
                form.post(route('document-types.store'), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.document_types));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, documentType?: DocumentType) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.document_types, edit: documentType });
    };

    const listDocumentTypes = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: 'api/v1/document-types?page_size=100', search, transChoiceKey: 'trans.document_type' });
        isLoading.value = false;
        documentTypes.value = data.value;
    };

    return {
        createDocumentTypeColumns,
        breadcrumbs,
        onOpenModal,
        saveDocumentType,
        isLoading,
        documentTypes,
        listDocumentTypes,
    };
};

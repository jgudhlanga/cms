import { useDataTables } from '@/composables/core/useDataTables';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { DocumentTemplate, DocumentTemplateParams } from '@/types/institution';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useDocumentTemplates = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, anchorTag } = useDataTables();
    const isLoading = ref(false);
    const documentTemplates = ref<DocumentTemplate[]>([]);
    const createDocumentTemplateColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.document_type', 1), accessorKey: 'attributes.documentType' },
            {
                header: trans('trans.preview'),
                accessorKey: 'preview',
                enableSorting: false,
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: DocumentTemplate } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return anchorTag({
                        title: trans('trans.preview'),
                        href: route('document-templates.preview', id),
                        classes: 'btn btn-sm btn-primary',
                    });
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: DocumentTemplate } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.document_template', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => {} },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:institution-settings'], route('document-templates.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:institution-settings'], route('document-templates.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:institution-settings'], route('document-templates.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const saveDocumentTemplate = (form: InertiaForm<DocumentTemplateParams>, documentTemplate?: DocumentTemplate) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.document_template', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.document_template', 1) });
            if (documentTemplate) {
                const id = getIdParams(documentTemplate.id?.toString() ?? '');
                form.put(route('document-templates.update', id), buildFormOptions(form, success, error));
            } else {
                form.post(route('document-templates.store'), buildFormOptions(form, success, error));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return {
        createDocumentTemplateColumns,
        documentTemplates,
        isLoading,
        saveDocumentTemplate,
    };
};

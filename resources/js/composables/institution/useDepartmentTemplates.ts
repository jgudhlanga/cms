import { useDataTables } from '@/composables/core/useDataTables';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { DocumentTemplate } from '@/types/institution';
import { usePage } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useDepartmentTemplates = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const documentTemplates = ref<DocumentTemplate[]>([]);
    const createDocumentTemplateColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
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

    return {
        createDocumentTemplateColumns,
        documentTemplates,
        isLoading,
    };
};

import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { WorkflowStep } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useWorkflowSteps = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const createWorkflowStepColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('#', 1), accessorKey: 'attributes.position', meta: { align: 'left' } },
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: WorkflowStep } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.name', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:settings'], route('workflow-steps.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:settings'], route('workflow-steps.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:settings'], route('workflow-steps.force-delete', id), name),
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
        { transChoiceKey: 'workflow_step' },
    ];

    const getName = () => trans_choice('trans.workflow_step', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const saveWorkflowStep = (form: InertiaForm<any>, workflowStep?: WorkflowStep) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);

            if (workflowStep) {
                const id = getIdParams(workflowStep.id?.toString() ?? '');
                form.put(
                    route('workflow-steps.update', id),
                    buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.workflow_steps),
                );
            } else {
                form.post(route('workflow-steps.store'), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.workflow_steps));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, workflowStep?: WorkflowStep) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.workflow_steps, edit: workflowStep });
    };

    const isLoading = ref(false);
    const workflowSteps = ref<WorkflowStep[]>([]);
    const listWorkflowSteps = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({
            url: 'api/v1/workflow-steps?page_size=100',
            search,
            transChoiceKey: 'trans.workflow_Step',
        });
        isLoading.value = false;
        workflowSteps.value = data.value;
    };
    return {
        createWorkflowStepColumns,
        breadcrumbs,
        onOpenModal,
        saveWorkflowStep,
        isLoading,
        workflowSteps,
        listWorkflowSteps,
    };
};

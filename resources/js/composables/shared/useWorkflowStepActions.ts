import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { WorkflowStepAction } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useWorkflowStepActions = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const createWorkflowStepActionColumns = () => {
        return [
            { header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: WorkflowStepAction } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.name', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:settings'), row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:settings'), route('workflow-step-actions.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:settings'), route('workflow-step-actions.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(hasAbility('forceDelete:settings'), route('workflow-step-actions.force-delete', id), name),
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
        { transChoiceKey: 'workflow_step_action' },
    ];

    const getName = () => trans_choice('trans.workflow_step_action', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const saveWorkflowStepAction = (form: InertiaForm<any>, workflowStepAction?: WorkflowStepAction) => {
        const { titleLabelSchema } = useSharedFormSchema();
        try {
            titleLabelSchema().parse(form);

            if (workflowStepAction) {
                const id = getIdParams(workflowStepAction.id?.toString() ?? '');
                form.put(
                    route('workflow-step-actions.update', id),
                    buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.workflow_step_actions),
                );
            } else {
                form.post(
                    route('workflow-step-actions.store'),
                    buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.workflow_step_actions),
                );
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, workflowStepAction?: WorkflowStepAction) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.workflow_step_actions, edit: workflowStepAction });
    };

    const isLoading = ref(false);
    const workflowStepActions = ref<WorkflowStepAction[]>([]);
    const listWorkflowStepActions = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({
            url: 'api/v1/workflow-step-actions?page_size=100',
            search,
            transChoiceKey: 'trans.workflow_Step_action',
        });
        isLoading.value = false;
        workflowStepActions.value = data.value;
    };
    return {
        createWorkflowStepActionColumns,
        breadcrumbs,
        onOpenModal,
        saveWorkflowStepAction,
        isLoading,
        workflowStepActions,
        listWorkflowStepActions,
    };
};

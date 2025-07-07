<script setup lang="ts">
import Title from '@/components/core/form/text/Title.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useWorkflowStepActions } from '@/composables/shared/useWorkflowStepActions';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { WorkflowStepAction, WorkflowStepActionParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const workflowStepAction = ref<WorkflowStepAction>();
const form = useForm<WorkflowStepActionParams>({
    title: '',
});

const { saveWorkflowStepAction } = useWorkflowStepActions();

const { modals } = useModalStore();

watch(modals!, () => {
    workflowStepAction.value = getModalEdit(APP_MODULE_KEYS.workflow_step_actions);
    form.title = workflowStepAction.value?.attributes?.title ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.workflow_step_actions"
        :title="`${workflowStepAction ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.workflow_step_action', 1)}`"
        :on-form-action="() => saveWorkflowStepAction(form, workflowStepAction)"
        :form="form"
    >
        <template #body>
            <Title :inputAutoFocus="true" v-model="form.title" @input="clearFormErrors(form, 'title')" :error="form.errors.title" />
        </template>
    </BaseModal>
</template>

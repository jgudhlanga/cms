<script setup lang="ts">
import Description from '@/components/core/form/text/Description.vue';
import Name from '@/components/core/form/text/Name.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useWorkflowSteps } from '@/composables/shared/useWorkflowSteps';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { WorkflowStep, WorkflowStepParams } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const workflowStep = ref<WorkflowStep>();
const form = useForm<WorkflowStepParams>({
    name: '',
    description: '',
});

const { saveWorkflowStep } = useWorkflowSteps();

const { modals } = useModalStore();

watch(modals!, () => {
    workflowStep.value = getModalEdit(APP_MODULE_KEYS.workflow_steps);
    form.name = workflowStep.value?.attributes?.name ?? '';
    form.description = workflowStep.value?.attributes?.description ?? '';
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.workflow_steps"
        :title="`${workflowStep ? $t('trans.create') : $t('trans.create')} ${$tChoice('trans.workflow_step', 1)}`"
        :on-form-action="() => saveWorkflowStep(form, workflowStep)"
        :form="form"
    >
        <template #body>
            <Name :inputAutoFocus="true" v-model="form.name" @input="clearFormErrors(form, 'name')" :error="form.errors.name" />
            <Description v-model="form.description" @input="clearFormErrors(form, 'description')" :error="form.errors.description" />
        </template>
    </BaseModal>
</template>

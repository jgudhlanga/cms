<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseCheckbox } from '@/components/core/form';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import SpinnerComponent from '@/components/core/util/SpinnerComponent.vue';
import { useRoles } from '@/composables/acl/useRoles';
import { useWorkflowStepActions } from '@/composables/shared/useWorkflowStepActions';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';

interface Props {
    institutionDepartmentId: string;
}

defineProps<Props>();

const { isLoading: actionsLoading, workflowStepActions, listWorkflowStepActions } = useWorkflowStepActions();
const { isLoading: rolesLoading, roles, listRoles } = useRoles();
onMounted(() => {
    listRoles();
    listWorkflowStepActions();
});

const form = useForm<any>({
    workflow_step_action_ids: [],
    role_ids: [],
    staff_ids: [],
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.department_workflow_actions"
        :title="$t('trans.workflow_step_action_metadata')"
        :on-form-action="() => {}"
        :form="form"
        :size="SizeVariant.full"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <BaseCard :title="$tChoice('trans.role', 2)" :description="$t('trans.step_role_description')">
                    <template v-if="rolesLoading">
                        <SpinnerComponent class="flex w-full" />
                    </template>
                    <template v-else>
                        <div class="grid grid-cols-1 gap-x-3 md:grid-cols-3">
                            <div class="flex items-center space-x-1" v-for="role in roles" :key="`role_key_${role['id']}`">
                                <BaseCheckbox
                                    :input-id="`role_id_${role['id']}`"
                                    :value="role['id']"
                                    v-model="form.role_ids"
                                    :label="role['attributes']['name']"
                                />
                            </div>
                        </div>
                    </template>
                </BaseCard>
                <BaseCard :title="$tChoice('trans.user', 2)" :description="$t('trans.step_user_description')"> Users </BaseCard>
                <BaseCard class="col-span-2" :title="$tChoice('trans.action', 2)" :description="$t('trans.step_action_description')">
                    <template v-if="actionsLoading">
                        <SpinnerComponent class="flex w-full" />
                    </template>
                    <template v-else>
                        <div class="grid grid-cols-1 gap-x-3 md:grid-cols-4">
                            <div class="flex items-center space-x-1" v-for="action in workflowStepActions" :key="`action_key_${action['id']}`">
                                <BaseCheckbox
                                    :input-id="`action_id_${action['id']}`"
                                    :value="action['id']"
                                    v-model="form.workflow_step_action_ids"
                                    :label="action['attributes']['title']"
                                />
                            </div>
                        </div>
                    </template>
                </BaseCard>
            </div>
        </template>
    </BaseModal>
</template>

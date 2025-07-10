<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseCheckbox } from '@/components/core/form';
import StaffSelect from '@/components/core/form/select/StaffSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import SpinnerComponent from '@/components/core/util/SpinnerComponent.vue';
import { useRoles } from '@/composables/acl/useRoles';
import { useDepartmentWorkflows } from '@/composables/institution/useDepartmentWorkflows';
import { useWorkflowStepActions } from '@/composables/shared/useWorkflowStepActions';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { DepartmentApplicationStep, DepartmentApplicationStepActionParams } from '@/types/department-meta-data';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

interface Props {
    institutionDepartmentId: string;
}

const step = ref<DepartmentApplicationStep>();
defineProps<Props>();

const { isLoading: actionsLoading, workflowStepActions, listWorkflowStepActions } = useWorkflowStepActions();
const { isLoading: rolesLoading, roles, listRoles } = useRoles();
const onlyRoles = 'head-of-department,head-of-division,lecturer,lecturer-in-charge,senior-lecturer,registrar,selection-officer,student';
const onlyStaffRoles = 'head-of-department,head-of-division,lecturer,lecturer-in-charge,senior-lecturer,registrar,selection-officer';

onMounted(async () => {
    await listRoles(`api/v1/acl/roles?page_size=all&only=${onlyRoles}`);
    await listWorkflowStepActions();
});
const { modals } = useModalStore();
const form = useForm<DepartmentApplicationStepActionParams>({
    department_application_step_id: null,
    workflow_action_ids: [],
    role_ids: [],
    staff_ids: [],
});
watch(modals!, async () => {
    step.value = getModalEdit(APP_MODULE_KEYS.department_workflow_actions);
    form.department_application_step_id = step.value?.id?.toString() ?? '';
    form.role_ids = step.value?.relationships?.metadata?.roleIds ?? [];
    form.staff_ids = step.value?.relationships?.metadata?.staffIds ?? [];
    form.workflow_action_ids = step.value?.relationships?.metadata?.workflowActionIds ?? [];
    form.defaults();
});
const { syncWorkflowStepActionMetadata } = useDepartmentWorkflows();
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.department_workflow_actions"
        :title="`${$t('trans.workflow_step_action_metadata')} (${step?.attributes?.workflowStep}) `"
        :on-form-action="() => syncWorkflowStepActionMetadata(institutionDepartmentId, form)"
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
                            <div class="flex items-center space-x-1" v-for="role in roles?.data" :key="`role_key_${role['id']}`">
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
                <BaseCard :title="$tChoice('trans.staff', 2)" :description="$t('trans.step_user_description')">
                    <template v-if="rolesLoading">
                        <SpinnerComponent class="flex w-full" />
                    </template>
                    <template v-else>
                        <div class="grid grid-cols-1 gap-x-3">
                            <StaffSelect
                                :url="`api/v1/staff?page_size=all&only[roles][]=${onlyStaffRoles}`"
                                :label-uppercase="true"
                                :is-multi="true"
                                :is-searchable="true"
                                v-model="form.staff_ids"
                            />
                        </div>
                    </template>
                </BaseCard>
                <BaseCard class="col-span-2" :title="$tChoice('trans.action', 2)" :description="$t('trans.step_action_description')">
                    <template v-if="actionsLoading">
                        <SpinnerComponent class="flex w-full" />
                    </template>
                    <template v-else>
                        <div class="grid grid-cols-1 gap-x-3 md:grid-cols-5">
                            <div class="flex items-center space-x-1" v-for="action in workflowStepActions" :key="`action_key_${action['id']}`">
                                <BaseCheckbox
                                    :input-id="`action_id_${action['id']}`"
                                    :value="action['id']"
                                    v-model="form.workflow_action_ids"
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

<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import SpinnerComponent from '@/components/core/util/SpinnerComponent.vue';
import { useDepartmentApplications } from '@/composables/institution/useDepartmentApplications';
import { useWorkflowSteps } from '@/composables/shared/useWorkflowSteps';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { DepartmentApplicationStepParams } from '@/types/department-meta-data';
import { WokflowStep } from '@/types/settings';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    institutionDepartmentId: string;
}

defineProps<Props>();

const allSelected = ref(false);
const form = useForm<DepartmentApplicationStepParams>({
    application_step_ids: [],
});

const { isLoading, applicationSteps, listApplicationSteps } = useWorkflowSteps();
const { syncDepartmentApplicationSteps } = useDepartmentApplications();
const selectAll = () => {
    if (allSelected.value) {
        form.application_step_ids = [];
        allSelected.value = false;
    } else {
        form.application_step_ids = applicationSteps.value?.map((item: WokflowStep) => item['id']);
        allSelected.value = true;
    }
};
const updateModel = () => {
    allSelected.value = form.application_step_ids?.length == applicationSteps.value?.length;
};
const { modals } = useModalStore();

watch(modals!, async () => {
    form.application_step_ids = getModalEdit(APP_MODULE_KEYS.department_application_steps);
    await listApplicationSteps();
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.department_application_steps"
        :title="$t('trans.subscribe_to_application_steps')"
        :on-form-action="() => syncDepartmentApplicationSteps(institutionDepartmentId, form)"
        :form="form"
        :size="SizeVariant.full"
    >
        <template #body>
            <div class="flex flex-col space-y-3">
                <template v-if="isLoading">
                    <SpinnerComponent class="w-full" />
                </template>
                <template v-else>
                    <div class="flex flex-col space-y-2">
                        <div class="flex">
                            <BaseCheckbox
                                input-id="select_all_courses"
                                :checked="allSelected"
                                :label="`${$t('trans.select_all')} ${$tChoice('trans.step', 2).toLowerCase()}`"
                                @click="selectAll()"
                            />
                        </div>
                        <div class="grid grid-cols-4 gap-x-3 md:grid-cols-2">
                            <template v-for="step in applicationSteps" :key="`step_key_${step['id']}`">
                                <div class="flex space-x-1 items-center">
                                    <BaseCheckbox
                                        :input-id="`step_id_${step['id']}`"
                                        :value="step['id']"
                                        v-model="form.application_step_ids"
                                        :label="step['attributes']['name']"
                                        @change="updateModel()"
                                    />
                                    <span class="text-xs text-muted-foreground">{{`(${step.attributes?.description})`}}</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </BaseModal>
</template>

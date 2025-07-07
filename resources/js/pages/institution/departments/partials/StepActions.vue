<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import SpinnerComponent from '@/components/core/util/SpinnerComponent.vue';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    institutionDepartmentId: string;
}

defineProps<Props>();
const form = useForm<any>({});
const isLoading = ref(false);
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.department_workflow_actions"
        :title="$t('trans.subscribe_to_application_steps')"
        :on-form-action="() => {}"
        :form="form"
        :size="SizeVariant.full"
    >
        <template #body>
            <div class="flex flex-col space-y-3">
                <template v-if="isLoading">
                    <SpinnerComponent class="w-full" />
                </template>
                <template v-else>
                    <div class="flex flex-col space-y-2">Mr Gudhlanga</div>
                </template>
            </div>
        </template>
    </BaseModal>
</template>

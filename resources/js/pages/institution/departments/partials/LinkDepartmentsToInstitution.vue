<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useDepartments } from '@/composables/institution/useDepartments';
import { useInstitutionDepartments } from '@/composables/institution/useInstitutionDepartments';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { Department, InstitutionDepartmentParams } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const allSelected = ref(false);
const params = route().params;
const { isItTrue } = useUtils();
const form = useForm<InstitutionDepartmentParams>({
    is_academic: isItTrue(params?.is_academic),
    department_ids: [],
});
const { isLoading, departments, listDepartments } = useDepartments();
const { syncInstitutionDepartments } = useInstitutionDepartments();
const selectAll = () => {
    if (allSelected.value) {
        form.department_ids = [];
        allSelected.value = false;
    } else {
        form.department_ids = departments.value?.data?.map((item: Department) => item['id']) ?? [];
        allSelected.value = true;
    }
};
const updateModel = () => {
    allSelected.value = form.department_ids?.length == departments.value?.data?.length;
};
const { modals } = useModalStore();

watch(modals!, async () => {
    form.department_ids = getModalEdit(APP_MODULE_KEYS.institution_departments);
    await listDepartments(route('v1.departments.index', { is_academic: params?.is_academic, page_size: 'all' }));
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.institution_departments"
        :title="$t('trans.link_department')"
        :on-form-action="() => syncInstitutionDepartments(form)"
        :form="form"
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
                                input-id="select_all_departments"
                                :checked="allSelected"
                                :label="`${$t('trans.select_all')} ${$tChoice('trans.department', 1).toLowerCase()}`"
                                @click="selectAll()"
                            />
                        </div>
                        <div class="grid grid-cols-1 gap-x-3 md:grid-cols-2">
                            <template v-for="department in departments?.data" :key="`department_key_${department['id']}`">
                                <BaseCheckbox
                                    :input-id="`department_id_${department['id']}`"
                                    :value="department['id']"
                                    v-model="form.department_ids"
                                    :label="department['attributes']['name']"
                                    @change="updateModel()"
                                />
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </BaseModal>
</template>

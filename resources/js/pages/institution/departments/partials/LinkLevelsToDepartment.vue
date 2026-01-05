<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useDepartmentLevels } from '@/composables/institution/useDepartmentLevels';
import { useLevels } from '@/composables/institution/useLevels';
import { SizeVariant } from '@/enums/sizes';
import { getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';
import { DepartmentLevelParams } from '@/types/department-meta-data';
import { Level } from '@/types/institution';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    institutionDepartmentId: string;
}

defineProps<Props>();

const allSelected = ref(false);
const form = useForm<DepartmentLevelParams>({
    level_ids: [],
    show_on_current_application_period: [],
});

const { isLoading, levels, listLevels } = useLevels();
const { syncDepartmentLevels } = useDepartmentLevels();
const selectAll = () => {
    if (allSelected.value) {
        form.level_ids = [];
        allSelected.value = false;
    } else {
        form.level_ids = levels.value?.map((item: Level) => item['id']);
        allSelected.value = true;
    }
};
const updateModel = () => {
    allSelected.value = form.level_ids?.length == levels.value?.length;
};
const { modals } = useModalStore();

watch(modals!, async () => {
    const data = getModalEdit(APP_MODULE_KEYS.department_levels);
    form.level_ids = data && data[0] ? data[0] : [];
    form.show_on_current_application_period = data && data[1] ? data[1] : [];
    await listLevels();
    form.defaults();
});
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.department_levels"
        :title="$t('trans.link_levels')"
        :on-form-action="() => syncDepartmentLevels(institutionDepartmentId, form)"
        :form="form"
        :size="SizeVariant.lg"
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
                                input-id="select_all_levels"
                                :checked="allSelected"
                                :label="`${$t('trans.select_all')} ${$tChoice('trans.level', 2).toLowerCase()}`"
                                @click="selectAll()"
                            />
                        </div>
                        <div class="grid grid-cols-1 gap-x-6 md:grid-cols-2">
                            <template v-for="level in levels" :key="`level_key_${level['id']}`">
                                <div class="flex justify-between">
                                    <BaseCheckbox
                                        :input-id="`level_id_${level['id']}`"
                                        :value="level['id']"
                                        v-model="form.level_ids"
                                        :label="level['attributes']['name']"
                                        @change="updateModel()"
                                    />
                                    <BaseCheckbox
                                        :input-id="`show_on_current_application_period_${level['id']}`"
                                        :value="level['id']"
                                        v-model="form.show_on_current_application_period"
                                        :label="$t('trans.show_on_current_application_period')"
                                        @change="updateModel()"
                                    />
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </BaseModal>
</template>

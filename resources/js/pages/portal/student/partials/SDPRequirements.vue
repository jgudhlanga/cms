<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { DepartmentLevelRequirement } from '@/types/department-meta-data';
import { storeToRefs } from 'pinia';
import { ref } from 'vue';

interface Props {
    levelRequirements?: DepartmentLevelRequirement | null;
}

const { read_write_acknowledged } = storeToRefs(useCreateApplicationFormStore());
const { isItTrue } = useUtils();

const readWriteAcknowledged = ref(false);
defineProps<Props>();

const acknowledgeReadWrite = (value: any) => {
    readWriteAcknowledged.value = value;
    if (read_write_acknowledged) {
        read_write_acknowledged.value = isItTrue(value);
    }
};
</script>

<template>
    <HeadingSmall :title="$t('trans.read_write_required')" :description="$t('trans.read_write_required_description')" />
    <BaseCheckbox
        input-id="read_write_acknowledged"
        @click="acknowledgeReadWrite($event.target.checked)"
        v-model="acknowledgeReadWrite"
        :label="`${$t('trans.acknowledge_read_and_write')}`"
    />
</template>

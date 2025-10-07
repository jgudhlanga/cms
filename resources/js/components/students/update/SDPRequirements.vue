<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { Enrolment } from '@/types/enrolments';
import { storeToRefs } from 'pinia';
import { onMounted, ref } from 'vue';

interface Props {
    isViewOnly?: boolean;
    application?: Enrolment | null;
}

const props = withDefaults(defineProps<Props>(), {
    isViewOnly: false,
});

const { isItTrue } = useUtils();
const { application } = props;

const isEditing = Number(String(application?.id)) > 0;
const store = isEditing ? useUpdateProgramFormStore() : useCreateApplicationFormStore();

const { read_write_acknowledged } = storeToRefs(store);

const readWriteAcknowledged = ref(false);

const acknowledgeReadWrite = (value: any) => {
    readWriteAcknowledged.value = value;
    if (read_write_acknowledged) {
        read_write_acknowledged.value = isItTrue(value);
    }
};

onMounted(() => {
    if (read_write_acknowledged && isEditing) read_write_acknowledged.value = isItTrue(application?.attributes?.readWriteAcknowledged);
    readWriteAcknowledged.value = isItTrue(read_write_acknowledged?.value);
});
</script>

<template>
    <HeadingSmall :title="$t('trans.read_write_required')" :description="$t('trans.read_write_required_description')" />
    <BaseCheckbox
        v-if="!isViewOnly"
        input-id="read_write_acknowledged"
        @click="acknowledgeReadWrite($event.target.checked)"
        v-model="readWriteAcknowledged"
        :label="`${$t('trans.acknowledge_read_and_write')}`"
    />
    <div v-else class="mt-4 flex items-center space-x-2">
        <component :is="icons[IconName.check]" />
        <span>{{ `${$t('trans.acknowledge_read_and_write')}` }}</span>
    </div>
</template>

<script setup lang="ts">
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { DepartmentLevelRequirement } from '@/types/department-meta-data';
import { storeToRefs } from 'pinia';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { BaseCheckbox } from '@/components/core/form';
import { ref } from 'vue';
import { useUtils } from '@/composables/core/useUtils';

interface Props {
    levelRequirements?: DepartmentLevelRequirement | null;
}

const { level, required_level_completed } = storeToRefs(useCreateApplicationFormStore());
const { isItTrue } = useUtils();

const requiredLevelCompleted = ref(false);
defineProps<Props>();

const acknowledgeLevelCompleted = (value: any) => {
    requiredLevelCompleted.value = value;
    if (required_level_completed) {
        required_level_completed.value = isItTrue(value);
    }
};
</script>

<template>
    <HeadingSmall
        :title="$t('trans.level_required', { level: levelRequirements?.attributes?.requiredLevel ?? '' })"
        :description="
            $t('trans.level_required_description', { level: levelRequirements?.attributes?.requiredLevel ?? '', current: level?.label ?? '' })
        "
    />
    <BaseCheckbox
        input-id="required_level_completed"
        @click="acknowledgeLevelCompleted($event.target.checked)"
        v-model="requiredLevelCompleted"
        :label="`${$t('trans.acknowledge_required_level_completed', { level: levelRequirements?.attributes?.requiredLevel ?? '' })}`"
    />
</template>

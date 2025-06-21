<script setup lang="ts">
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { DepartmentLevelRequirement } from '@/types/department-meta-data';
import { storeToRefs } from 'pinia';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { BaseCheckbox } from '@/components/core/form';
import { ref } from 'vue';
import { useUtils } from '@/composables/core/useUtils';
import {IconName, icons} from '@/lib/icons';

interface Props {
    isViewOnly?: boolean
    levelRequirements?: DepartmentLevelRequirement | null;
}

withDefaults(defineProps<Props>(), {
    isViewOnly: false
});

const { level, required_level_completed } = storeToRefs(useCreateApplicationFormStore());
const { isItTrue } = useUtils();

const requiredLevelCompleted = ref(false);

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
        v-if="!isViewOnly"
        input-id="required_level_completed"
        @click="acknowledgeLevelCompleted($event.target.checked)"
        v-model="requiredLevelCompleted"
        :label="`${$t('trans.acknowledge_required_level_completed', { level: levelRequirements?.attributes?.requiredLevel ?? '' })}`"
    />
    <div v-else class="flex space-x-2 mt-4 items-center">
        <component :is="icons[IconName.check]"/>
        <span>{{ `${$t('trans.acknowledge_required_level_completed', { level: levelRequirements?.attributes?.requiredLevel ?? '' })}`}}</span>
    </div>
</template>

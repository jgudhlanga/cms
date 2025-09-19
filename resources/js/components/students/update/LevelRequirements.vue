<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import { IconName, icons } from '@/lib/icons';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { DepartmentLevelRequirement } from '@/types/department-meta-data';
import { storeToRefs } from 'pinia';
import { ref } from 'vue';

interface Props {
    isViewOnly?: boolean;
    levelRequirements?: DepartmentLevelRequirement | null;
}

withDefaults(defineProps<Props>(), {
    isViewOnly: false,
});

const { level, required_level_completed } = storeToRefs(useCreateApplicationFormStore());
const { isItTrue } = useUtils();

const requiredLevelCompleted = ref(required_level_completed?.value ?? false);

const acknowledgeLevelCompleted = (value: any) => {
    requiredLevelCompleted.value = value;
    if (required_level_completed) {
        required_level_completed.value = isItTrue(value);
    }
};
/*const handleUploadFileChange = (event: any) => {
    const upload = event.target.files[0];
    if (!upload) return;
    if (required_level_upload) {
        required_level_upload.value = upload;
    }
};*/
</script>

<template>
    <HeadingSmall
        :title="$t('trans.level_required', { level: levelRequirements?.attributes?.requiredLevel ?? '' })"
        :description="
            $t('trans.level_required_description', { level: levelRequirements?.attributes?.requiredLevel ?? '', current: level?.label ?? '' })
        "
    />
    <div v-if="!isViewOnly" class="flex flex-col space-y-6">
        <BaseCheckbox
            input-id="required_level_completed"
            @click="acknowledgeLevelCompleted($event.target.checked)"
            v-model="requiredLevelCompleted"
            :label="`${$t('trans.acknowledge_required_level_completed', { level: levelRequirements?.attributes?.requiredLevel ?? '' })}`"
        />
        <!--<BaseInput
            input-id="required_level_upload"
            classes="w-1/3"
            :label-uppercase="true"
            :label="`${$t('trans.upload')} ${levelRequirements?.attributes?.requiredLevel} ${$tChoice('trans.certificate', 1)}`"
            :type="TextFieldType.file"
            @change="handleUploadFileChange"
        />-->
    </div>
    <div v-else class="mt-4 flex items-center space-x-2">
        <component :is="icons[IconName.check]" />
        <span>{{ `${$t('trans.acknowledge_required_level_completed', { level: levelRequirements?.attributes?.requiredLevel ?? '' })}` }}</span>
    </div>
</template>

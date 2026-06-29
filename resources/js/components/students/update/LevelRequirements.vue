<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseCheckbox } from '@/components/core/form';
import { TypeVariant } from '@/enums/type-variants';
import { useUtils } from '@/composables/core/useUtils';
import { IconName, icons } from '@/lib/icons';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { CourseRequirement, DepartmentLevelRequirement } from '@/types/department-meta-data';
import { Enrolment } from '@/types/enrolments';
import { storeToRefs } from 'pinia';
import { onMounted, ref } from 'vue';

interface Props {
    isViewOnly?: boolean;
    requirements?: DepartmentLevelRequirement | CourseRequirement | null;
    application?: Enrolment | null;
}

const props = withDefaults(defineProps<Props>(), {
    isViewOnly: false,
});

const { isItTrue } = useUtils();
const { application } = props;

const isEditing = Number(String(application?.id)) > 0;
const store = isEditing ? useUpdateProgramFormStore() : useCreateApplicationFormStore();

const { level, required_level_completed } = storeToRefs(store);

const requiredLevelCompleted = ref(required_level_completed?.value ?? false);

const acknowledgeLevelCompleted = (value: any) => {
    requiredLevelCompleted.value = value;
    if (required_level_completed) {
        required_level_completed.value = isItTrue(value);
    }
};

onMounted(() => {
    if (required_level_completed && isEditing) required_level_completed.value = isItTrue(application?.attributes?.requiredLevelCompleted);
    requiredLevelCompleted.value = isItTrue(required_level_completed?.value);
});
/*const handleUploadFileChange = (event: any) => {
    const upload = event.target.files[0];
    if (!upload) return;
    if (required_level_upload) {
        required_level_upload.value = upload;
    }
};*/
</script>

<template>
    <BaseAlert
        class="my-4"
        :type="TypeVariant.warning"
        :title="$t('trans.level_required', { level: requirements?.attributes?.requiredLevel ?? '' })"
        :description="
            $t('trans.level_required_description', {
                level: requirements?.attributes?.requiredLevel ?? '',
                current: level?.label ?? '',
            })
        "
    >
        <div v-if="!isViewOnly" class="mt-3 border-t border-amber-200 pt-3">
            <BaseCheckbox
                input-id="required_level_completed"
                @click="acknowledgeLevelCompleted($event.target.checked)"
                v-model="requiredLevelCompleted"
                :label="`${$t('trans.acknowledge_required_level_completed', { level: requirements?.attributes?.requiredLevel ?? '' })}`"
            />
        </div>
        <div v-else class="mt-3 flex items-center space-x-2 border-t border-amber-200 pt-3 font-medium">
            <component :is="icons[IconName.check]" />
            <span>{{ `${$t('trans.acknowledge_required_level_completed', { level: requirements?.attributes?.requiredLevel ?? '' })}` }}</span>
        </div>
    </BaseAlert>
</template>

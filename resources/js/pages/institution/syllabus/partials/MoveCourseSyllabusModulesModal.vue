<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { SizeVariant } from '@/enums/sizes';
import type { SelectOption } from '@/types/utils';
import type { InertiaForm } from '@inertiajs/vue3';

defineProps<{
    modalName: string;
    moveTargetOptions: SelectOption[];
    onFormAction: () => void;
    onCloseModal: () => void;
}>();

const form = defineModel<
    InertiaForm<{
        course_syllabus_module_ids: number[];
        target_academic_year_option_id: number | null;
    }>
>('form', { required: true });
</script>

<template>
    <BaseModal
        :name="modalName"
        :title="$t('syllabus.move_modules_modal_title')"
        :form="form"
        :on-form-action="onFormAction"
        :on-close-modal="onCloseModal"
        :size="SizeVariant.sm"
        cancel-btn-text="trans.close"
        action-btn-text="syllabus.move_modules_submit"
    >
        <template #body>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium uppercase" for="move_target_period">{{
                    $t('syllabus.move_modules_select_target')
                }}</label>
                <select
                    id="move_target_period"
                    v-model.number="form.target_academic_year_option_id"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    :class="{ 'border-red-500': form.errors.target_academic_year_option_id }"
                >
                    <option v-for="option in moveTargetOptions" :key="option.value" :value="Number(option.value)">
                        {{ option.label }}
                    </option>
                </select>
                <p v-if="form.errors.target_academic_year_option_id" class="text-sm text-red-600">
                    {{ form.errors.target_academic_year_option_id }}
                </p>
                <p v-if="form.errors.course_syllabus_module_ids" class="text-sm text-red-600">
                    {{ form.errors.course_syllabus_module_ids }}
                </p>
            </div>
        </template>
    </BaseModal>
</template>

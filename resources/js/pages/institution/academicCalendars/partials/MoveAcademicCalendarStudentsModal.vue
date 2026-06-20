<script setup lang="ts">
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { SizeVariant } from '@/enums/sizes';
import type { AcademicCalendarClassMoveTarget } from '@/types/academic-calendar';
import type { InertiaForm } from '@inertiajs/vue3';

defineProps<{
    modalName: string;
    moveTargetClasses: AcademicCalendarClassMoveTarget[];
    onFormAction: () => void;
    onCloseModal: () => void;
}>();

const form = defineModel<
    InertiaForm<{
        student_enrolment_ids: number[];
        target_academic_calendar_class_id: number | null;
    }>
>('form', { required: true });
</script>

<template>
    <BaseModal
        :name="modalName"
        :title="$t('academic_calendar.move_students_modal_title')"
        :form="form"
        :on-form-action="onFormAction"
        :on-close-modal="onCloseModal"
        :size="SizeVariant.sm"
        cancel-btn-text="trans.close"
        action-btn-text="academic_calendar.move_students_submit"
    >
        <template #body>
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium uppercase" for="move_target_class">{{ $t('academic_calendar.move_students_select_target') }}</label>
                <select
                    id="move_target_class"
                    v-model.number="form.target_academic_calendar_class_id"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                    :class="{ 'border-red-500': form.errors.target_academic_calendar_class_id }"
                >
                    <option v-for="c in moveTargetClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <p v-if="form.errors.target_academic_calendar_class_id" class="text-sm text-red-600">
                    {{ form.errors.target_academic_calendar_class_id }}
                </p>
                <p v-if="form.errors.student_enrolment_ids" class="text-sm text-red-600">{{ form.errors.student_enrolment_ids }}</p>
            </div>
        </template>
    </BaseModal>
</template>

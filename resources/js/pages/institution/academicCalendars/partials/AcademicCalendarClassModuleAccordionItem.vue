<script setup lang="ts">
import BaseAccordionItem from '@/components/core/accordion/BaseAccordionItem.vue';
import SelectLecturerSelect from '@/components/core/form/select/SelectLecturerSelect.vue';
import type { ModuleLecturerFeedback } from '@/composables/academicCalendars/useClassModuleLecturerSave';
import type { ClassSemesterModule } from '@/types/academic-calendar';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    module: ClassSemesterModule;
    institutionDepartmentId: number;
    canAssignStaffing: boolean;
    moduleStaffIds: number[];
    isDirty: boolean;
    isSaving: boolean;
    feedback: ModuleLecturerFeedback;
}>();

const emit = defineEmits<{
    'update:moduleStaffIds': [value: number[]];
    save: [];
}>();

const staffIdsModel = computed({
    get: () => props.moduleStaffIds ?? [],
    set: (value: number[]) => emit('update:moduleStaffIds', value),
});

const staffIdsCount = computed(() => (props.moduleStaffIds ?? []).length);

const lecturerCountLabel = computed(() => {
    const count = (props.moduleStaffIds ?? []).length;

    if (count === 0) {
        return trans('academic_calendar.module_lecturers_unassigned');
    }

    return trans_choice('academic_calendar.module_lecturers_count', count, { count });
});

const accordionValue = computed(() => `module-${props.module.moduleId}`);
</script>

<template>
    <BaseAccordionItem :value="accordionValue" :title="`${module.code} — ${module.title}`">
        <template #trigger-extra>
            <span
                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium"
                :class="
                    staffIdsCount > 0
                        ? 'border-green-200 bg-green-50 text-green-700'
                        : 'border-amber-200 bg-amber-50 text-amber-800'
                "
            >
                {{ lecturerCountLabel }}
            </span>
            <span
                v-if="module.captureMarkOnly"
                class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-800"
            >
                {{ $t('academic_calendar.course_work_mark_only_badge') }}
            </span>
            <span
                v-if="isDirty"
                class="inline-flex items-center rounded-full border border-border bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground"
            >
                {{ $t('academic_calendar.module_lecturers_unsaved') }}
            </span>
        </template>

        <p
            v-if="module.syllabusDefaultStaffIds.length > 0 && staffIdsCount === 0"
            class="text-xs text-muted-foreground"
        >
            {{ $t('academic_calendar.syllabus_defaults_hint', { names: module.syllabusDefaultStaffIds.length }) }}
        </p>

        <div class="flex w-fit max-w-full flex-col gap-2 pt-1">
            <div
                v-if="canAssignStaffing"
                class="flex items-center gap-3"
            >
                <span class="shrink-0 text-sm font-medium text-foreground">
                    {{ $tChoice('syllabus.lecturer', 2) }}
                </span>
                <SelectLecturerSelect
                    v-model="staffIdsModel"
                    label=""
                    class="w-64 shrink-0"
                    width-class="w-64"
                    :vertical-layout="false"
                    :institution-department-id="institutionDepartmentId"
                />
                <button
                    type="button"
                    class="shrink-0 cursor-pointer font-medium text-primary underline-offset-4 decoration-primary/50 transition-colors duration-300 ease-out hover:underline disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="isSaving"
                    @click="emit('save')"
                >
                    {{ isSaving ? $t('trans.saving') : $t('academic_calendar.save_module_lecturers') }}
                </button>
            </div>

            <p
                v-if="feedback"
                class="text-sm"
                :class="feedback.type === 'success' ? 'text-green-700' : 'text-destructive'"
            >
                {{ feedback.message }}
            </p>
        </div>
    </BaseAccordionItem>
</template>

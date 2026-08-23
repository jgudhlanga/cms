<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import SelectLecturerSelect from '@/components/core/form/select/SelectLecturerSelect.vue';
import type { ModuleLecturerFeedback } from '@/composables/academicCalendars/useClassModuleLecturerSave';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { ClassSemesterModule } from '@/types/academic-calendar';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

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

const isExpanded = ref(false);

const staffIdsModel = computed({
    get: () => props.moduleStaffIds ?? [],
    set: (value: number[]) => emit('update:moduleStaffIds', value),
});

const staffIdsCount = computed(() => (props.moduleStaffIds ?? []).length);

const lecturerLabel = computed(() => {
    const ids = props.moduleStaffIds ?? [];

    if (ids.length === 0) {
        return trans('academic_calendar.module_lecturers_unassigned');
    }

    const namesById = new Map<number, string>();
    (props.module.staffIds ?? []).forEach((id, index) => {
        const name = props.module.staffNames?.[index];
        if (name) {
            namesById.set(id, name);
        }
    });

    const names = ids.map((id) => namesById.get(id)).filter((name): name is string => Boolean(name));

    if (names.length > 0) {
        return names.join(', ');
    }

    return trans_choice('academic_calendar.module_lecturers_count', ids.length, { count: ids.length });
});
</script>

<template>
    <div class="rounded-md border border-border/70 bg-background px-2.5 py-1.5">
        <button
            type="button"
            class="flex w-full items-center justify-between gap-2 text-left"
            :aria-expanded="isExpanded"
            @click="isExpanded = !isExpanded"
        >
            <span class="min-w-0 truncate text-xs font-medium text-foreground" :title="`${module.code} — ${module.title}`">
                {{ module.title }}
            </span>
            <span class="inline-flex shrink-0 items-center gap-1">
                <span
                    class="max-w-[12rem] truncate text-[11px]"
                    :class="staffIdsCount > 0 ? 'text-foreground' : 'text-muted-foreground'"
                    :title="lecturerLabel"
                >
                    {{ lecturerLabel }}
                </span>
                <span
                    v-if="module.captureMarkOnly"
                    class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-1.5 py-px text-[10px] font-medium text-blue-800"
                >
                    {{ $t('academic_calendar.course_work_mark_only_badge') }}
                </span>
                <span
                    v-if="isDirty"
                    class="inline-flex items-center rounded-full border border-border bg-muted px-1.5 py-px text-[10px] font-medium text-muted-foreground"
                >
                    {{ $t('academic_calendar.module_lecturers_unsaved') }}
                </span>
            </span>
        </button>

        <div v-if="isExpanded" class="mt-2 space-y-2 border-t border-border/50 pt-2">
            <p
                v-if="module.syllabusDefaultStaffIds.length > 0 && staffIdsCount === 0"
                class="text-[11px] text-muted-foreground"
            >
                {{ $t('academic_calendar.syllabus_defaults_hint', { names: module.syllabusDefaultStaffIds.length }) }}
            </p>

            <div v-if="canAssignStaffing" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <SelectLecturerSelect
                    v-model="staffIdsModel"
                    label=""
                    class="min-w-0 w-full sm:flex-1"
                    width-class="w-full"
                    :vertical-layout="false"
                    :institution-department-id="institutionDepartmentId"
                />
                <BaseButton
                    type="button"
                    :size="ButtonSize.xs"
                    :variant="ColorVariant.primary_outline"
                    classes="rounded-full shrink-0"
                    :processing="isSaving"
                    @click="emit('save')"
                >
                    {{ isSaving ? $t('trans.saving') : $t('academic_calendar.save_module_lecturers') }}
                </BaseButton>
            </div>

            <p
                v-if="feedback"
                class="text-xs"
                :class="feedback.type === 'success' ? 'text-green-700' : 'text-destructive'"
            >
                {{ feedback.message }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import BaseAccordionItem from '@/components/core/accordion/BaseAccordionItem.vue';
import SelectAcademicYearOptionSelect from '@/components/core/form/select/SelectAcademicYearOptionSelect.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseTag from '@/components/core/util/BaseTag.vue';
import AcademicCalendarClassModuleAccordionItem from '@/pages/institution/academicCalendars/partials/AcademicCalendarClassModuleAccordionItem.vue';
import { useClassModuleLecturerSave } from '@/composables/academicCalendars/useClassModuleLecturerSave';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, successAlert } from '@/lib/alerts';
import type { ClassSemesterModule } from '@/types/academic-calendar';
import { router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps<{
    institutionDepartmentId: number;
    calendarYear: string;
    academicCalendarClassId: number;
    semesterModules: ClassSemesterModule[];
    selectedAcademicYearOptionId: number | null;
    calendarType: 'term' | 'semester' | 'abma';
    semesterConfigHasSyllabi: boolean;
    canAssignStaffing: boolean;
}>();

const { open: openConfirmDialog } = useCustomConfirmDialog();

const localSemesterModules = ref<ClassSemesterModule[]>([...props.semesterModules]);
const moduleStaffIds = reactive<Record<number, number[]>>({});
const expandedModules = ref<string[]>([]);

const copyDefaultsUrl = computed(() =>
    route('academic-calendars.department-classes.copy-module-lecturer-defaults', {
        institution_department: String(props.institutionDepartmentId),
        calendar_year: props.calendarYear,
        academic_calendar_class: String(props.academicCalendarClassId),
    }),
);

const syncModuleUrl = computed(() =>
    route('academic-calendars.department-classes.sync-module-lecturers', {
        institution_department: String(props.institutionDepartmentId),
        calendar_year: props.calendarYear,
        academic_calendar_class: String(props.academicCalendarClassId),
    }),
);

const {
    savingModuleId,
    copyingDefaults,
    moduleFeedback,
    saveModuleLecturers,
    copyDefaults,
    initSavedStaffIds,
    isModuleDirty,
} = useClassModuleLecturerSave(
    () => syncModuleUrl.value,
    () => copyDefaultsUrl.value,
    () => props.selectedAcademicYearOptionId,
);

const syncModuleStaffIds = (modules: ClassSemesterModule[]): void => {
    for (const module of modules) {
        moduleStaffIds[module.moduleId] = [...module.staffIds];
    }
};

const applySemesterModules = (modules: ClassSemesterModule[]): void => {
    localSemesterModules.value = [...modules];
    syncModuleStaffIds(modules);
    initSavedStaffIds(modules);
};

applySemesterModules(props.semesterModules);

watch(
    () => props.semesterModules,
    (modules) => {
        applySemesterModules(modules);
    },
    { deep: true },
);

const selectedSemester = computed({
    get: () => props.selectedAcademicYearOptionId,
    set: (value: number | null) => {
        const currentUrl = new URL(window.location.href);

        if (value == null) {
            currentUrl.searchParams.delete('academic_year_option_id');
        } else {
            currentUrl.searchParams.set('academic_year_option_id', String(value));
        }

        router.get(currentUrl.pathname + currentUrl.search, {}, { preserveScroll: true, preserveState: false });
    },
});

const handleSaveModule = async (module: ClassSemesterModule): Promise<void> => {
    const staffIds = moduleStaffIds[module.moduleId] ?? [];
    await saveModuleLecturers(module, staffIds);
};

const handleCopyDefaults = async (): Promise<void> => {
    if (props.selectedAcademicYearOptionId == null) {
        return;
    }

    const confirmed = await openConfirmDialog({
        title: trans('academic_calendar.copy_syllabus_defaults'),
        message: trans('academic_calendar.copy_syllabus_defaults_confirm'),
        confirmText: trans('trans.confirm'),
        cancelText: trans('trans.cancel'),
    });

    if (!confirmed) {
        return;
    }

    const updatedModules = await copyDefaults();

    if (updatedModules == null) {
        const copyError = moduleFeedback[-1];
        if (copyError?.type === 'error') {
            errorAlert(copyError.message);
        }

        return;
    }

    applySemesterModules(updatedModules);
    successAlert(trans('academic_calendar.module_lecturers_copied_success'));
};

const hasModules = computed(() => localSemesterModules.value.length > 0);

const moduleCountLabel = computed(
    () => `${localSemesterModules.value.length} ${trans_choice('trans.module', localSemesterModules.value.length)}`,
);
</script>

<template>
    <BaseAccordion class="w-full">
        <BaseAccordionItem
            value="module-lecturers"
            :title="$t('academic_calendar.module_lecturers')"
        >
            <template #trigger-extra>
                <div
                    class="ml-auto flex shrink-0 flex-wrap items-center justify-end gap-2"
                    @click.stop
                    @mousedown.stop
                >
                    <div class="w-48 shrink-0">
                        <SelectAcademicYearOptionSelect
                            v-model="selectedSemester"
                            :calendar-type="calendarType"
                        />
                    </div>
                    <BaseButton
                        v-if="canAssignStaffing && selectedAcademicYearOptionId != null && semesterConfigHasSyllabi"
                        type="button"
                        :title="$t('academic_calendar.copy_syllabus_defaults')"
                        :variant="ColorVariant.primary_outline"
                        :size="ButtonSize.xs"
                        classes="rounded-full shrink-0"
                        :processing="copyingDefaults"
                        @click.stop="handleCopyDefaults"
                    />
                    <BaseTag
                        v-if="selectedAcademicYearOptionId != null"
                        :title="moduleCountLabel"
                        :variant="ColorVariant.fuchsia_outline"
                        classes="cursor-default text-[10px] font-medium"
                    />
                </div>
            </template>

            <div class="space-y-3">
                <p
                    v-if="selectedAcademicYearOptionId != null && !semesterConfigHasSyllabi"
                    class="text-sm text-amber-700"
                >
                    {{ $t('academic_calendar.semester_config_missing') }}
                </p>

                <Empty
                    v-else-if="selectedAcademicYearOptionId != null && !hasModules"
                    :message="$t('academic_calendar.no_modules_for_semester')"
                />

                <BaseAccordion
                    v-else-if="selectedAcademicYearOptionId != null && hasModules"
                    v-model="expandedModules"
                >
                    <AcademicCalendarClassModuleAccordionItem
                        v-for="module in localSemesterModules"
                        :key="module.moduleId"
                        :module-staff-ids="moduleStaffIds[module.moduleId] ?? []"
                        :module="module"
                        :institution-department-id="institutionDepartmentId"
                        :can-assign-staffing="canAssignStaffing"
                        :is-dirty="isModuleDirty(module.moduleId, moduleStaffIds[module.moduleId] ?? [])"
                        :is-saving="savingModuleId[module.moduleId] === true"
                        :feedback="moduleFeedback[module.moduleId] ?? null"
                        @update:module-staff-ids="(staffIds) => (moduleStaffIds[module.moduleId] = staffIds)"
                        @save="handleSaveModule(module)"
                    />
                </BaseAccordion>
            </div>
        </BaseAccordionItem>
    </BaseAccordion>
</template>

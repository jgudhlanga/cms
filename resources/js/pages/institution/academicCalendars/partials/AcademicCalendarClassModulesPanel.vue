<script setup lang="ts">
import SelectSemesterSelect from '@/components/core/form/select/SelectSemesterSelect.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import AcademicCalendarClassModuleAccordionItem from '@/pages/institution/academicCalendars/partials/AcademicCalendarClassModuleAccordionItem.vue';
import { useClassModuleLecturerSave } from '@/composables/academicCalendars/useClassModuleLecturerSave';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, successAlert } from '@/lib/alerts';
import type { ClassSemesterModule } from '@/types/academic-calendar';
import { router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps<{
    institutionDepartmentId: number;
    calendarYear: string;
    academicCalendarClassId: number;
    semesterModules: ClassSemesterModule[];
    selectedSemesterId: number | null;
    calendarType: 'term' | 'semester' | 'abma';
    semesterConfigHasSyllabi: boolean;
    canAssignStaffing: boolean;
    embedded?: boolean;
}>();

const { open: openConfirmDialog } = useCustomConfirmDialog();

const localSemesterModules = ref<ClassSemesterModule[]>([...props.semesterModules]);
const moduleStaffIds = reactive<Record<number, number[]>>({});
const isOpen = ref(true);

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
    () => props.selectedSemesterId,
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
    get: () => props.selectedSemesterId,
    set: (value: number | null) => {
        const currentUrl = new URL(window.location.href);

        if (value == null) {
            currentUrl.searchParams.delete('semester_id');
        } else {
            currentUrl.searchParams.set('semester_id', String(value));
        }

        router.get(currentUrl.pathname + currentUrl.search, {}, { preserveScroll: true, preserveState: false });
    },
});

const handleSaveModule = async (module: ClassSemesterModule): Promise<void> => {
    const staffIds = moduleStaffIds[module.moduleId] ?? [];
    const result = await saveModuleLecturers(module, staffIds);

    if (result == null) {
        return;
    }

    const index = localSemesterModules.value.findIndex((row) => row.moduleId === module.moduleId);
    if (index !== -1) {
        localSemesterModules.value[index] = {
            ...localSemesterModules.value[index],
            staffIds: result.staffIds,
            staffNames: result.staffNames ?? [],
        };
    }
};

const handleCopyDefaults = async (): Promise<void> => {
    if (props.selectedSemesterId == null) {
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
    <div :class="embedded ? '' : 'overflow-hidden rounded-lg border border-border/60 bg-muted/20'">
        <div class="flex flex-wrap items-center gap-2 px-2.5 py-2">
            <button
                type="button"
                class="inline-flex min-w-0 items-center gap-1.5 text-left"
                :aria-expanded="isOpen"
                @click="isOpen = !isOpen"
            >
                <span class="text-xs font-semibold uppercase text-foreground">{{ $t('academic_calendar.module_lecturers') }}</span>
                <span v-if="selectedSemesterId != null" class="truncate text-[11px] text-muted-foreground">
                    {{ moduleCountLabel }}
                </span>
            </button>
            <div class="ml-auto flex shrink-0 flex-wrap items-center justify-end gap-1.5">
                <div class="w-40 shrink-0">
                    <SelectSemesterSelect
                        v-model="selectedSemester"
                        :calendar-type="calendarType"
                    />
                </div>
                <BaseButton
                    v-if="canAssignStaffing && selectedSemesterId != null && semesterConfigHasSyllabi"
                    type="button"
                    :title="$t('academic_calendar.copy_syllabus_defaults')"
                    :variant="ColorVariant.primary_outline"
                    :size="ButtonSize.xs"
                    classes="rounded-full shrink-0"
                    :processing="copyingDefaults"
                    @click.stop="handleCopyDefaults"
                />
                <button
                    type="button"
                    class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-border text-muted-foreground"
                    :aria-label="$t('academic_calendar.module_lecturers')"
                    :aria-expanded="isOpen"
                    @click="isOpen = !isOpen"
                >
                    <ChevronDown class="h-3.5 w-3.5 transition-transform" :class="isOpen ? 'rotate-180' : ''" />
                </button>
            </div>
        </div>

        <div v-if="isOpen" class="space-y-2 border-t border-border/50 px-2.5 py-2">
            <p
                v-if="selectedSemesterId != null && !semesterConfigHasSyllabi"
                class="text-xs text-amber-700"
            >
                {{ $t('academic_calendar.semester_config_missing') }}
            </p>

            <Empty
                v-else-if="selectedSemesterId != null && !hasModules"
                :message="$t('academic_calendar.no_modules_for_semester')"
            />

            <div
                v-else-if="selectedSemesterId != null && hasModules"
                class="grid grid-cols-1 gap-1.5 sm:grid-cols-2"
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
            </div>
        </div>
    </div>
</template>

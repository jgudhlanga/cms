<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import AcademicCalendarClassModuleAccordionItem from '@/pages/institution/academicCalendars/partials/AcademicCalendarClassModuleAccordionItem.vue';
import { useClassModuleLecturerSave } from '@/composables/academicCalendars/useClassModuleLecturerSave';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, successAlert } from '@/lib/alerts';
import type { ClassSemesterModule } from '@/types/academic-calendar';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        institutionDepartmentId: number;
        calendarYear: string;
        academicCalendarClassId: number;
        semesterModules: ClassSemesterModule[];
        selectedSemesterId: number | null;
        periodLabel?: string | null;
        semesterConfigHasSyllabi: boolean;
        canAssignStaffing: boolean;
        embedded?: boolean;
    }>(),
    {
        periodLabel: null,
        embedded: false,
    },
);

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

const hasPeriod = computed(() => props.selectedSemesterId != null || Boolean(props.periodLabel));

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
                <span v-if="hasPeriod" class="truncate text-[11px] text-muted-foreground">
                    {{ moduleCountLabel }}
                </span>
            </button>
            <div class="ml-auto flex shrink-0 flex-wrap items-center justify-end gap-1.5">
                <span
                    v-if="periodLabel"
                    class="inline-flex items-center rounded-full border border-border bg-background px-2 py-0.5 text-[11px] font-medium text-foreground"
                >
                    {{ periodLabel }}
                </span>
                <BaseButton
                    v-if="canAssignStaffing && hasPeriod && semesterConfigHasSyllabi"
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
                v-if="hasPeriod && !semesterConfigHasSyllabi"
                class="text-xs text-amber-700"
            >
                {{ $t('academic_calendar.semester_config_missing') }}
            </p>

            <Empty
                v-else-if="hasPeriod && !hasModules"
                :message="$t('academic_calendar.no_modules_for_semester')"
            />

            <div
                v-else-if="hasPeriod && hasModules"
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

<script setup lang="ts">
import AdminDepartmentCourseComboSelect from '@/components/core/form/combobox/AdminDepartmentCourseComboSelect.vue';
import AdminDepartmentLevelComboSelect from '@/components/core/form/combobox/AdminDepartmentLevelComboSelect.vue';
import AdminInstitutionDepartmentComboSelect from '@/components/core/form/combobox/AdminInstitutionDepartmentComboSelect.vue';
import ModeOfStudyComboSelect from '@/components/core/form/combobox/ModeOfStudyComboSelect.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import type { ProgrammeUsageRecord } from '@/types/programme-reassign';
import type { SelectOption } from '@/types/utils';
import type { InertiaForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const UNSPECIFIED_MODE_ID = 0;

const props = defineProps<{
    form: InertiaForm<{
        application_ids: number[];
        student_enrolment_ids: number[];
        institution_department_id: number | null;
        department_level_id: number | null;
        department_course_id: number | null;
        mode_of_study_id: number | null;
        department: SelectOption | null;
        level: SelectOption | null;
        course: SelectOption | null;
        modeOfStudy: SelectOption | null;
    }>;
    records: ProgrammeUsageRecord[];
    loadingRecords?: boolean;
    hydratingDefaults?: boolean;
    onFormAction: () => void;
}>();

const selectedApplicationIds = defineModel<number[]>('selectedApplicationIds', { required: true });
const filterModeIds = ref<number[]>([]);

const recordModeId = (row: ProgrammeUsageRecord): number =>
    row.mode_of_study_id !== null && row.mode_of_study_id > 0 ? row.mode_of_study_id : UNSPECIFIED_MODE_ID;

const modeOptions = computed(() => {
    const counts = new Map<number, { label: string; count: number }>();

    props.records.forEach((row) => {
        const id = recordModeId(row);
        const existing = counts.get(id);
        const label =
            id === UNSPECIFIED_MODE_ID
                ? trans('students.reassign_programme_unspecified_mode')
                : row.mode_of_study || `#${id}`;

        if (existing) {
            existing.count += 1;
            return;
        }

        counts.set(id, { label, count: 1 });
    });

    return [...counts.entries()]
        .map(([id, meta]) => ({ id, ...meta }))
        .sort((a, b) => a.label.localeCompare(b.label));
});

const filteredRecords = computed(() => {
    if (filterModeIds.value.length === 0) {
        return props.records;
    }

    const selectedModes = new Set(filterModeIds.value);

    return props.records.filter((row) => selectedModes.has(recordModeId(row)));
});

const visibleIds = computed(() => filteredRecords.value.map((row) => row.application_id));

const visibleSelectedCount = computed(
    () => visibleIds.value.filter((id) => selectedApplicationIds.value.includes(id)).length,
);

const allVisibleSelected = computed(
    () => visibleIds.value.length > 0 && visibleSelectedCount.value === visibleIds.value.length,
);

const someVisibleSelected = computed(
    () => visibleSelectedCount.value > 0 && !allVisibleSelected.value,
);

const selectAllCheckboxState = computed<boolean | 'indeterminate'>(() => {
    if (allVisibleSelected.value) {
        return true;
    }

    return someVisibleSelected.value ? 'indeterminate' : false;
});

const isModeFilterActive = (modeId: number): boolean => filterModeIds.value.includes(modeId);

const clearModeFilters = (): void => {
    filterModeIds.value = [];
};

const toggleModeFilter = (modeId: number): void => {
    if (isModeFilterActive(modeId)) {
        filterModeIds.value = filterModeIds.value.filter((id) => id !== modeId);
        return;
    }

    filterModeIds.value = [...filterModeIds.value, modeId];
};

const isChecked = (applicationId: number): boolean => selectedApplicationIds.value.includes(applicationId);

const toggleRecord = (applicationId: number, checked: boolean): void => {
    if (checked) {
        if (!selectedApplicationIds.value.includes(applicationId)) {
            selectedApplicationIds.value = [...selectedApplicationIds.value, applicationId];
        }
        return;
    }

    selectedApplicationIds.value = selectedApplicationIds.value.filter((id) => id !== applicationId);
};

const selectAllVisible = (): void => {
    const next = new Set(selectedApplicationIds.value);
    visibleIds.value.forEach((id) => next.add(id));
    selectedApplicationIds.value = [...next];
};

const deselectAllVisible = (): void => {
    const visible = new Set(visibleIds.value);
    selectedApplicationIds.value = selectedApplicationIds.value.filter((id) => !visible.has(id));
};

const toggleAllVisible = (checked: boolean): void => {
    if (checked) {
        selectAllVisible();
        return;
    }

    deselectAllVisible();
};

watch(
    () => props.records,
    () => {
        filterModeIds.value = [];
    },
);

watch(
    () => props.form.department?.value,
    (next, previous) => {
        if (props.hydratingDefaults || !previous || next === previous) {
            return;
        }
        props.form.level = null;
        props.form.course = null;
        props.form.modeOfStudy = null;
        clearFormErrors(props.form, 'level');
        clearFormErrors(props.form, 'course');
        clearFormErrors(props.form, 'modeOfStudy');
    },
);

watch(
    () => props.form.level?.value,
    (next, previous) => {
        if (props.hydratingDefaults || !previous || next === previous) {
            return;
        }
        props.form.course = null;
        props.form.modeOfStudy = null;
        clearFormErrors(props.form, 'course');
        clearFormErrors(props.form, 'modeOfStudy');
    },
);

watch(
    () => props.form.course?.value,
    (next, previous) => {
        if (props.hydratingDefaults || !previous || next === previous) {
            return;
        }
        props.form.modeOfStudy = null;
        clearFormErrors(props.form, 'modeOfStudy');
    },
);
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.reassign_programme"
        :title="$t('students.reassign_programme')"
        :form="form"
        :on-form-action="onFormAction"
        :size="SizeVariant.full"
        action-btn-text="students.reassign_programme"
    >
        <template #body>
            <div class="flex min-h-0 flex-1 flex-col gap-5">
                <p class="text-muted-foreground shrink-0 text-sm">{{ $t('students.reassign_programme_description') }}</p>

                <div class="grid shrink-0 grid-cols-1 gap-3 lg:grid-cols-4">
                    <AdminInstitutionDepartmentComboSelect
                        :form="form"
                        v-model="form.department"
                        :error="form.errors.department ?? form.errors.institution_department_id"
                        :is-required="true"
                    />
                    <AdminDepartmentLevelComboSelect
                        :form="form"
                        :institution-department-id="String(form.department?.value ?? '')"
                        v-model="form.level"
                        :error="form.errors.level ?? form.errors.department_level_id"
                        :is-required="true"
                    />
                    <AdminDepartmentCourseComboSelect
                        :form="form"
                        :department-level-id="String(form.level?.value ?? '')"
                        v-model="form.course"
                        :error="form.errors.course ?? form.errors.department_course_id"
                        :is-required="true"
                    />
                    <ModeOfStudyComboSelect
                        :form="form"
                        :institution-department-id="String(form.department?.value ?? '')"
                        :department-course-id="String(form.course?.value ?? '')"
                        :department-level-id="String(form.level?.value ?? '')"
                        v-model="form.modeOfStudy"
                        :error="form.errors.modeOfStudy ?? form.errors.mode_of_study_id"
                        :is-required="true"
                        :include-catalogue-modes="true"
                    />
                </div>

                <div class="border-border flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border">
                    <div class="border-border bg-muted/30 flex shrink-0 flex-col gap-3 border-b px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-foreground text-xs font-semibold tracking-wide uppercase">
                                {{ $t('students.reassign_programme_filter_modes') }}
                            </p>
                            <p class="text-muted-foreground text-xs">
                                {{
                                    $t('students.reassign_programme_selection_summary', {
                                        selected: visibleSelectedCount,
                                        visible: filteredRecords.length,
                                        total: selectedApplicationIds.length,
                                    })
                                }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                :class="
                                    filterModeIds.length === 0
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-border text-muted-foreground hover:bg-muted/50'
                                "
                                @click="clearModeFilters"
                            >
                                {{ $t('students.reassign_programme_all_modes') }}
                                <span class="text-muted-foreground font-normal">({{ records.length }})</span>
                            </button>
                            <button
                                v-for="mode in modeOptions"
                                :key="`reassign_mode_filter_${mode.id}`"
                                type="button"
                                class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                :class="
                                    isModeFilterActive(mode.id)
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-border text-muted-foreground hover:bg-muted/50'
                                "
                                @click="toggleModeFilter(mode.id)"
                            >
                                {{ mode.label }}
                                <span class="font-normal">({{ mode.count }})</span>
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    :checked="selectAllCheckboxState"
                                    :aria-label="$t('students.reassign_programme_select_filtered')"
                                    :disabled="visibleIds.length === 0"
                                    @update:checked="(checked) => toggleAllVisible(checked === true)"
                                />
                                <span class="text-foreground text-xs font-semibold uppercase">{{ $t('trans.select_all') }}</span>
                            </div>
                            <BaseButton
                                type="button"
                                :size="ButtonSize.xs"
                                :variant="ColorVariant.primary_outline"
                                classes="rounded-full"
                                :disabled="visibleIds.length === 0 || allVisibleSelected"
                                @click="selectAllVisible"
                            >
                                {{ $t('students.reassign_programme_select_filtered') }}
                            </BaseButton>
                            <BaseButton
                                type="button"
                                :size="ButtonSize.xs"
                                :variant="ColorVariant.shade"
                                classes="rounded-full"
                                :disabled="visibleSelectedCount === 0"
                                @click="deselectAllVisible"
                            >
                                {{ $t('students.reassign_programme_deselect_filtered') }}
                            </BaseButton>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <p v-if="loadingRecords" class="text-muted-foreground px-4 py-6 text-sm">{{ $t('trans.loading_data') }}</p>
                        <p v-else-if="records.length === 0" class="text-muted-foreground px-4 py-6 text-sm">
                            {{ $t('students.reassign_programme_none_selected') }}
                        </p>
                        <p v-else-if="filteredRecords.length === 0" class="text-muted-foreground px-4 py-6 text-sm">
                            {{ $t('students.reassign_programme_no_matches') }}
                        </p>
                        <ul v-else class="divide-border divide-y">
                            <li v-for="row in filteredRecords" :key="row.application_id" class="flex items-start gap-3 px-4 py-2.5">
                                <Checkbox
                                    :checked="isChecked(row.application_id)"
                                    :aria-label="row.student_name || String(row.application_id)"
                                    @update:checked="(checked) => toggleRecord(row.application_id, checked === true)"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ row.student_name || '—' }}</p>
                                    <p class="text-muted-foreground truncate text-[11px]">
                                        <template v-if="row.department || row.level || row.course">
                                            {{ [row.department, row.level, row.course].filter(Boolean).join(' · ') }}
                                            ·
                                        </template>
                                        {{ row.mode_of_study || $t('students.reassign_programme_unspecified_mode') }}
                                        <span v-if="row.intake_period"> · {{ row.intake_period }}</span>
                                        ·
                                        {{
                                            row.has_enrolment
                                                ? $t('students.reassign_programme_has_enrolment')
                                                : $t('students.reassign_programme_application_only')
                                        }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </template>
    </BaseModal>
</template>

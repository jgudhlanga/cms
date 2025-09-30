<script setup lang="ts">
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import SelectSitting from '@/components/students/update/SelectSitting.vue';
import SelectYear from '@/components/students/update/SelectYear.vue';
import { useGrades } from '@/composables/institution/useGrades';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { EXAM_SITTINGS } from '@/lib/constants';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { AcademicOLevelResult, Enrolment } from '@/types/enrolments';
import { RadioGroupOption } from '@/types/forms';
import { Grade, Subject } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { onMounted, ref, Ref, watchEffect } from 'vue';

interface Props {
    application?: Enrolment | null;
}

const props = defineProps<Props>();
const { application } = props;

const isEditing = Number(String(application?.id)) > 0;
const store = isEditing ? useUpdateProgramFormStore() : useCreateApplicationFormStore();
const { o_level_subject_ids, o_level_years, o_level_sittings, levelRequirements } = storeToRefs(store);
const { listGrades, isLoading, grades } = useGrades();
const { getMainSittingYear, getMainSitting } = useStudentPortal();

function ensureObjectRef<T extends object>(refObj: Ref<T | undefined | null> | undefined, defaultValue: T): T {
    if (!refObj) throw new Error('Ref is undefined');
    if (!refObj.value) {
        refObj.value = defaultValue;
    }
    return refObj.value;
}

const onRadioChange = (value: string) => {
    const [subjectId, gradeId] = value.split('|');
    if (!subjectId || !gradeId) return;

    const subjectData = ensureObjectRef(o_level_subject_ids, {});
    const yearData = ensureObjectRef(o_level_years, {});
    const sittingData = ensureObjectRef(o_level_sittings, {});

    if (gradeId === 'remove') {
        delete subjectData[subjectId];
        delete yearData[subjectId];
        delete sittingData[subjectId];
    } else {
        subjectData[subjectId] = gradeId;
    }
};
const onYearChange = (value: string, subjectId: string) => {
    if (!o_level_years) {
        return;
    }
    if (!o_level_years.value) {
        o_level_years.value = {};
    }
    o_level_years.value[subjectId] = value;
};

const onSittingChange = (value: any, subjectId: string) => {
    if (!o_level_sittings) {
        return;
    }
    if (!o_level_sittings.value) {
        o_level_sittings.value = {};
    }
    o_level_sittings.value[subjectId] = value;
};

const getOptionsForSubject = (subject: Subject): RadioGroupOption[] => {
    if (!subject || !grades.value) return [];

    const dataOptions = grades.value
        .filter((grade: Grade) => Number(grade.attributes.position) < 4)
        .map((grade: Grade) => ({
            value: `${subject.id}|${grade.id}`,
            label: grade.attributes?.name,
            inputId: `radio_${subject.id}_${grade.id}`,
        }));
    dataOptions.push({
        value: `${subject.id}|remove`,
        label: trans('trans.remove').toUpperCase() as string,
        inputId: `radio_${subject.id}_remove`,
    });
    return dataOptions;
};

const getDefaultOLevels = (subject: Subject) => {
    if (!subject || !o_level_subject_ids?.value || !grades.value) return null;
    const gradeId = o_level_subject_ids.value[subject?.id ?? ''];
    if (!gradeId) return null;
    const grade = grades.value.find((g: Grade) => g.id == gradeId);
    if (!grade) return null;
    return `${subject.id}|${gradeId}`;
};

const mainSitting = ref<SelectOption | null>(null);
const onMainSittingChange = (value: SelectOption | null) => {
    mainSitting.value = value;
    // Ensure o_level_sittings is defined
    if (!o_level_sittings) return;
    // Initialize value object if needed
    if (!o_level_sittings.value) {
        o_level_sittings.value = {};
    }
    // Safely access levelRequirements.relationships
    const subjects = levelRequirements?.value?.relationships?.subjects;
    if (!subjects) return; // nothing to update
    subjects.forEach((subject: Subject) => {
        const subjectId = subject.id?.toString() ?? '';
        if (!subjectId) return; // skip invalid IDs
        if (value) {
            o_level_sittings.value![subjectId] = value;
        } else {
            delete o_level_sittings.value![subjectId];
        }
    });
};

const mainYear = ref<string | null>(null);
const onMainYearChange = (value: string | null) => {
    mainYear.value = value;
    // Ensure o_level_years is defined
    if (!o_level_years) return;
    // Initialize value object if needed
    if (!o_level_years.value) {
        o_level_years.value = {};
    }
    // Safely access levelRequirements.relationships
    const subjects = levelRequirements?.value?.relationships?.subjects;
    if (!subjects) return; // nothing to update
    subjects.forEach((subject: Subject) => {
        const subjectId = subject.id?.toString() ?? '';
        if (!subjectId) return; // skip invalid IDs
        if (value) {
            o_level_years.value![subjectId] = value;
        } else {
            delete o_level_years.value![subjectId];
        }
    });
};
onMounted(async () => {
    await listGrades();

    //set all subjects to main year and sitting from application
    watchEffect(() => {
        if (!isLoading.value && grades.value?.length) {
            populateCurrentDataFromApplication();
        }
    });
    // === Main Year logic ===
    if (o_level_years?.value) {
        mainYear.value = getMainSittingYear(o_level_years.value);
    }

    // === Main Sitting logic ===
    if (o_level_sittings?.value) {
        mainSitting.value = getMainSitting(o_level_sittings.value);
    }
});

const populateCurrentDataFromApplication = () => {
    if (application?.relationships?.oLevelResults?.length) {
        const subjects = levelRequirements?.value?.relationships?.subjects as Subject[] | undefined;
        if (!subjects?.length) return;
        subjects.forEach((subject) => {
            const subjectId = subject.id?.toString();
            if (!subjectId) return;
            // find the matching O-Level result for this subject
            const result = application?.relationships?.oLevelResults?.find(
                (r: AcademicOLevelResult) => r.attributes.subjectId.toString() === subjectId,
            );
            //===================== YEARS =============================
            if (!o_level_years) return;
            if (!o_level_years.value) {
                o_level_years.value = {};
            }
            if (result) {
                o_level_years.value[subjectId] = String(result.attributes.examYear);
            } else {
                delete o_level_years.value[subjectId];
            }
            //===================== SITTINGS =============================
            if (!o_level_sittings) return;
            if (!o_level_sittings.value) {
                o_level_sittings.value = {};
            }
            const sittingLabel =
                EXAM_SITTINGS.find((sitting) => sitting.value === result?.attributes?.examSitting)?.label ?? result?.attributes?.examSitting;
            if (result) {
                o_level_sittings.value[subjectId] = { value: result?.attributes?.examSitting, label: String(sittingLabel ?? '') };
            }
            //===================== GRADES =============================
            if (!o_level_subject_ids) return;
            if (!o_level_subject_ids.value) {
                o_level_subject_ids.value = {};
            }
            if (result) {
                o_level_subject_ids.value[subjectId] = String(result?.attributes?.gradeId);
            }
        });
    }
};
</script>

<template>
    <HeadingSmall
        :title="`${$t('trans.o_level_main_subjects')} (${levelRequirements?.attributes?.mainSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />
    <template v-if="levelRequirements?.relationships?.subjects && levelRequirements.relationships.subjects.length > 0">
        <div class="flex w-full flex-col overflow-auto">
            <table class="hava-table my-4">
                <thead class="hava-thead">
                    <tr>
                        <th class="hava-th text-left">{{ $tChoice('trans.subject', 1) }}</th>
                        <th class="hava-th text-center">
                            {{ $tChoice('trans.year', 1) }}
                        </th>
                        <th class="hava-th text-center">{{ $tChoice('trans.sitting', 1) }}</th>
                        <th class="hava-th text-center">{{ $tChoice('trans.grade', 1) }}</th>
                    </tr>
                </thead>
                <tbody class="hava-tbody">
                    <tr class="hava-tr">
                        <td class="hava-td">
                            <span class="text-primary font-bold">{{ $t('trans.select_for_all') }}</span>
                        </td>
                        <td class="hava-td">
                            <div class="flex items-center justify-center">
                                <SelectYear input-id="main_year" :model-value="mainYear" @update:model-value="(value) => onMainYearChange(value)" />
                            </div>
                        </td>
                        <td class="hava-td">
                            <SelectSitting :model-value="mainSitting" @update:modelValue="(option: SelectOption) => onMainSittingChange(option)" />
                        </td>
                        <td class="hava-td"></td>
                    </tr>
                    <tr class="hava-tr" v-for="subject in levelRequirements.relationships.subjects" :key="subject?.id ?? ''">
                        <td class="hava-td">{{ subject?.attributes?.name }}</td>
                        <td class="hava-td">
                            <div class="flex items-center justify-center">
                                <SelectYear
                                    :input-id="`year_${subject.id}`"
                                    :model-value="o_level_years?.[subject?.id?.toString() ?? ''] || null"
                                    @update:model-value="(value) => onYearChange(value, subject?.id ?? '')"
                                />
                            </div>
                        </td>
                        <td class="hava-td">
                            <SelectSitting
                                :model-value="o_level_sittings?.[subject?.id?.toString() ?? ''] || null"
                                @update:modelValue="(option: SelectOption) => onSittingChange(option, subject?.id ?? '')"
                            />
                        </td>
                        <td class="hava-td">
                            <SpinnerComponent class="flex items-center justify-center" v-if="isLoading" />
                            <template v-else>
                                <BaseRadioGroup
                                    class="flex items-center justify-center"
                                    :options="getOptionsForSubject(subject)"
                                    :default-value="getDefaultOLevels(subject)"
                                    :label-uppercase="true"
                                    :is-required="true"
                                    orientation="horizontal"
                                    @update:modelValue="onRadioChange"
                                />
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </template>
    <template v-else>
        <Empty :message="$t('trans.no_main_subjects_found')" />
    </template>
</template>

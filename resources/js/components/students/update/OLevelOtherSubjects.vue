<script setup lang="ts">
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import SelectOtherSubject from '@/components/students/update/SelectOtherSubject.vue';
import SelectSitting from '@/components/students/update/SelectSitting.vue';
import SelectYear from '@/components/students/update/SelectYear.vue';
import { useGrades } from '@/composables/institution/useGrades';
import { useSubjects } from '@/composables/institution/useSubjects';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { EXAM_SITTINGS } from '@/lib/constants';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { Enrolment } from '@/types/enrolments';
import { RadioGroupOption } from '@/types/forms';
import { Grade, Subject } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref, Ref, watchEffect } from 'vue';

interface Props {
    application?: Enrolment | null;
}

const props = defineProps<Props>();
const { application } = props;

const isEditing = Number(String(application?.id)) > 0;

const store = isEditing ? useUpdateProgramFormStore() : useCreateApplicationFormStore();
const { o_level_other_subject_ids, o_level_other_grade_ids, o_level_other_years, o_level_other_sittings, levelRequirements, courseRequirements } =
    storeToRefs(store);
const { listSubjects, isLoading: subjectsLoading, subjects } = useSubjects();
const { listGrades, isLoading: gradesLoading, grades } = useGrades();

const requirements = computed(() => {
    if (courseRequirements && courseRequirements.value && Number(String(courseRequirements.value?.id)) > 0) {
        return courseRequirements.value;
    }
    if (levelRequirements && levelRequirements.value && Number(String(levelRequirements.value?.id)) > 0) {
        return levelRequirements.value;
    }
    return null;
});
const { getMainSittingYear, getMainSitting } = useStudentPortal();
function ensureObjectRef<T extends object>(refObj: Ref<T | undefined | null> | undefined, defaultValue: T): T {
    if (!refObj) throw new Error('Ref is undefined');
    if (!refObj.value) {
        refObj.value = defaultValue;
    }
    return refObj.value;
}
const mainSubjectIds = requirements?.value?.attributes?.mainSubjectIds ?? [];
const options = computed(() => {
    return subjects.value
        .filter((subject: Subject) => !mainSubjectIds.includes(Number(subject.id)))
        .map(
            (subject: Subject) =>
                <SelectOption>{
                    value: Number(subject.id),
                    label: subject?.attributes?.name,
                },
        );
});

const otherSubjects = computed(() => {
    return subjects.value.filter((subject: Subject) => !mainSubjectIds.includes(Number(subject.id)));
});

const onGradeChange = (value: string) => {
    const [index, gradeId] = value.split('|');
    if (!index || !gradeId) return;

    const gradeData = ensureObjectRef(o_level_other_grade_ids, {});
    gradeData[index] = gradeId;
};
const onYearChange = (value: string, index: string) => {
    if (!o_level_other_years) {
        return;
    }
    if (!o_level_other_years.value) {
        o_level_other_years.value = {};
    }
    o_level_other_years.value[index] = value;
};

const onSubjectChange = (value: any, index: string) => {
    if (!o_level_other_subject_ids) {
        return;
    }
    if (!o_level_other_subject_ids.value) {
        o_level_other_subject_ids.value = {};
    }
    o_level_other_subject_ids.value[index] = value;
};
const onSittingChange = (value: any, index: string) => {
    if (!o_level_other_sittings) {
        return;
    }
    if (!o_level_other_sittings.value) {
        o_level_other_sittings.value = {};
    }
    o_level_other_sittings.value[index] = value;
};

const getOptionsForSubject = (index: string): RadioGroupOption[] => {
    if (!index || !grades.value) return [];
    return grades.value
        .filter((grade: Grade) => Number(grade.attributes.position) < 4)
        .map((grade: Grade) => ({
            value: `${index}|${grade.id}`,
            label: grade.attributes?.name,
            inputId: `radio_${index}_${grade.id}`,
        }));
};

const getDefaultOLevels = (index: string) => {
    if (!index || !o_level_other_grade_ids?.value || !grades.value) return null;
    const gradeId = o_level_other_grade_ids.value[index ?? ''];
    if (!gradeId) return null;
    const grade = grades.value.find((g: Grade) => g.id == gradeId);
    if (!grade) return null;
    return `${index}|${gradeId}`;
};

const mainSitting = ref<SelectOption | null>(null);
const onMainSittingChange = (value: SelectOption | null) => {
    mainSitting.value = value;
    // Ensure o_level_other_sittings is defined
    if (!o_level_other_sittings) return;
    // Initialize value object if needed
    if (!o_level_other_sittings.value) {
        o_level_other_sittings.value = {};
    }
    const subjectCount = Number(requirements?.value?.attributes.otherSubjectsCount);
    if (subjectCount == 0) return; // nothing to update
    for (let i = 1; i <= subjectCount; i++) {
        if (value) {
            o_level_other_sittings.value![i] = value;
        } else {
            delete o_level_other_sittings.value![i];
        }
    }
};

const mainYear = ref<string | null>(null);
const onMainYearChange = (value: string | null) => {
    mainYear.value = value;
    // Ensure o_level_other_years is defined
    if (!o_level_other_years) return;
    // Initialize value object if needed
    if (!o_level_other_years.value) {
        o_level_other_years.value = {};
    }
    const subjectCount = Number(requirements?.value?.attributes.otherSubjectsCount);
    if (subjectCount == 0) return; // nothing to update
    for (let i = 1; i <= subjectCount; i++) {
        if (value) {
            o_level_other_years.value![i] = value;
        } else {
            delete o_level_other_years.value![i];
        }
    }
};
onMounted(() => {
    listGrades();
    listSubjects();

    // === Main Year logic ===
    if (o_level_other_years?.value) {
        mainYear.value = getMainSittingYear(o_level_other_years.value);
    }

    // === Main Sitting logic ===
    if (o_level_other_sittings?.value) {
        mainSitting.value = getMainSitting(o_level_other_sittings.value);
    }

    watchEffect(() => {
        if (!subjectsLoading.value && subjects.value?.length) {
            populateCurrentDataFromApplication();
        }
    });
});
const populateCurrentDataFromApplication = () => {
    const oLevelResults = application?.relationships?.oLevelResults ?? [];
    const subjects = otherSubjects.value as Subject[] | undefined;
    if (!subjects?.length || !oLevelResults.length) return;

    const count = Number(requirements?.value?.attributes?.otherSubjectsCount) || 0;
    if (!count) return;

    // Filter subjects to only those that have matching O-Level results
    const matchedSubjects = subjects
        .map((subject) => {
            const subjectId = subject.id?.toString();
            if (!subjectId) return null;

            const result = oLevelResults.find((r) => r.attributes?.subjectId?.toString().trim() === subjectId);
            if (!result) return null;

            return {
                subjectId,
                label: String(result.attributes.subject),
                examYear: String(result.attributes.examYear),
                examSitting: String(result.attributes.examSitting),
                gradeId: String(result.attributes.gradeId),
            };
        })
        .filter(Boolean) as { subjectId: string; label: string; examYear: string; examSitting: string; gradeId: string }[];

    // Take only the first `count` unique subjects

    matchedSubjects.slice(0, count).forEach((subj, index) => {
        //============== SUBJECTS ===========================
        if (!o_level_other_subject_ids) return;
        if (!o_level_other_subject_ids.value) o_level_other_subject_ids.value = {};
        o_level_other_subject_ids.value[String(index + 1)] = {
            value: subj.subjectId,
            label: subj.label,
        };
        // ============== YEARS ===========================
        if (!o_level_other_years) {
            return;
        }
        if (!o_level_other_years.value) {
            o_level_other_years.value = {};
        }
        o_level_other_years.value[index + 1] = subj.examYear;
        // ============== SITTING ===========================
        if (!o_level_other_sittings) {
            return;
        }
        if (!o_level_other_sittings.value) {
            o_level_other_sittings.value = {};
        }
        const sittingLabel = EXAM_SITTINGS.find((sitting) => sitting.value === subj.examSitting)?.label ?? subj.examSitting;
        o_level_other_sittings.value[index + 1] = {
            value: subj.examSitting,
            label: sittingLabel,
        };
        // ============== GRADES ===========================
        if (!o_level_other_grade_ids) return;
        if (!o_level_other_grade_ids.value) o_level_other_grade_ids.value = {};
        o_level_other_grade_ids.value[index + 1] = subj.gradeId;
    });
};
</script>

<template>
    <HeadingSmall
        :title="`${$t('trans.o_level_other_subjects')} (${requirements?.attributes?.otherSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />
    <template v-if="requirements?.attributes && Number(requirements?.attributes?.otherSubjectsCount) > 0">
        <div class="flex w-full flex-col overflow-auto">
            <table class="hava-table my-4">
                <thead class="hava-thead">
                    <tr>
                        <th class="hava-th text-left">{{ $tChoice('trans.subject', 1) }}</th>
                        <th class="hava-th text-center">{{ $tChoice('trans.year', 1) }}</th>
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
                    <tr class="hava-tr" v-for="n in requirements?.attributes?.otherSubjectsCount" :key="n">
                        <td class="hava-td text-left">
                            <SpinnerComponent class="flex items-center justify-center" v-if="subjectsLoading || gradesLoading" />
                            <SelectOtherSubject
                                v-else
                                :options="options"
                                :input-id="`other_subject_${n}`"
                                :model-value="o_level_other_subject_ids?.[n?.toString() ?? ''] || null"
                                @update:model-value="(option: SelectOption) => onSubjectChange(option, n.toString() ?? '')"
                            />
                        </td>
                        <td class="hava-td">
                            <div class="flex items-center justify-center">
                                <SelectYear
                                    :input-id="`other_year_${n}`"
                                    :model-value="o_level_other_years?.[n?.toString() ?? ''] || null"
                                    @update:model-value="(value: string) => onYearChange(value, n.toString() ?? '')"
                                />
                            </div>
                        </td>
                        <td class="hava-td text-center">
                            <SelectSitting
                                :model-value="o_level_other_sittings?.[n.toString() ?? ''] || null"
                                @update:modelValue="(option: SelectOption) => onSittingChange(option, n.toString() ?? '')"
                            />
                        </td>
                        <td class="hava-td text-center">
                            <SpinnerComponent class="flex items-center justify-center" v-if="gradesLoading || subjectsLoading" />
                            <template v-else>
                                <BaseRadioGroup
                                    class="flex items-center justify-center"
                                    :options="getOptionsForSubject(n.toString() ?? '')"
                                    :default-value="getDefaultOLevels(n.toString() ?? '')"
                                    :label-uppercase="true"
                                    :is-required="true"
                                    orientation="horizontal"
                                    @update:modelValue="onGradeChange"
                                />
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </template>
    <template v-else>
        <Empty :message="$t('trans.no_other_subjects_found')" />
    </template>
</template>

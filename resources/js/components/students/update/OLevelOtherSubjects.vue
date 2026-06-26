<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import ItemLabel from '@/components/students/update/mobile/ItemLabel.vue';
import OLevelGradeButtons from '@/components/students/update/OLevelGradeButtons.vue';
import SelectExamYear from '@/components/students/update/SelectExamYear.vue';
import SelectOtherSubject from '@/components/students/update/SelectOtherSubject.vue';
import SelectSitting from '@/components/students/update/SelectSitting.vue';
import { useGrades } from '@/composables/institution/useGrades';
import { useSubjects } from '@/composables/institution/useSubjects';
import { EXAM_SITTINGS } from '@/lib/constants';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { Enrolment } from '@/types/enrolments';
import { RadioGroupOption } from '@/types/forms';
import { Grade, Subject } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { storeToRefs } from 'pinia';
import { computed, onMounted, Ref, watchEffect } from 'vue';

interface Props {
    application?: Enrolment | null;
    isViewOnly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isViewOnly: false,
});
const { application } = props;

const isEditing = Number(String(application?.id)) > 0;

const store = isEditing ? useUpdateProgramFormStore() : useCreateApplicationFormStore();
const {
    o_level_other_subject_ids,
    o_level_other_grade_ids,
    o_level_other_years,
    o_level_other_sittings,
    o_level_primary_year,
    o_level_primary_sitting,
    o_level_other_resit_rows,
    levelRequirements,
    courseRequirements,
} = storeToRefs(store);
const examDateOfBirth = computed(() => (isEditing ? null : useCreateApplicationFormStore().date_of_birth));
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

function ensureObjectRef<T extends object>(refObj: Ref<T | undefined | null> | undefined, defaultValue: T): T {
    if (!refObj) throw new Error('Ref is undefined');
    if (!refObj.value) {
        refObj.value = defaultValue;
    }
    return refObj.value;
}

const mainSubjectIds = computed(() => requirements.value?.attributes?.mainSubjectIds ?? []);

const options = computed(() => {
    return subjects.value
        .filter((subject: Subject) => !mainSubjectIds.value.includes(Number(subject.id)))
        .map(
            (subject: Subject) =>
                <SelectOption>{
                    value: Number(subject.id),
                    label: subject?.attributes?.name,
                },
        );
});

const otherSubjects = computed(() => {
    return subjects.value.filter((subject: Subject) => !mainSubjectIds.value.includes(Number(subject.id)));
});

const isResitRow = (index: string): boolean => Boolean(o_level_other_resit_rows.value?.[index]);

const applyPrimaryToRow = (index: string) => {
    if (isResitRow(index)) {
        return;
    }
    const yearData = ensureObjectRef(o_level_other_years, {});
    const sittingData = ensureObjectRef(o_level_other_sittings, {});
    if (o_level_primary_year.value) {
        yearData[index] = o_level_primary_year.value;
    }
    if (o_level_primary_sitting.value) {
        sittingData[index] = o_level_primary_sitting.value;
    }
};

const onResitToggle = (index: string, checked: boolean) => {
    const resitData = ensureObjectRef(o_level_other_resit_rows, {});
    resitData[index] = checked;
    if (!checked) {
        applyPrimaryToRow(index);
    }
};

const onGradeChange = (value: string) => {
    const [index, gradeId] = value.split('|');
    if (!index || !gradeId) return;

    const gradeData = ensureObjectRef(o_level_other_grade_ids, {});
    gradeData[index] = gradeId;
    applyPrimaryToRow(index);
};

const onSubjectChange = (value: SelectOption, index: string) => {
    const subjectData = ensureObjectRef(o_level_other_subject_ids, {});
    subjectData[index] = value;
    applyPrimaryToRow(index);
};

const onSittingChange = (value: SelectOption | null, index: string) => {
    const sittingData = ensureObjectRef(o_level_other_sittings, {});
    sittingData[index] = value ?? ({} as SelectOption);
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

const getSelectedGradeId = (index: string): string | null => o_level_other_grade_ids?.value?.[index] ?? null;

const selectGrade = (index: string, gradeId: string) => {
    onGradeChange(`${index}|${gradeId}`);
};

const inferResitRowsFromApplication = () => {
    const count = Number(requirements.value?.attributes?.otherSubjectsCount) || 0;
    if (!count) return;
    const resitData = ensureObjectRef(o_level_other_resit_rows, {});
    const primaryYear = o_level_primary_year.value;
    const primarySitting = o_level_primary_sitting.value?.value;

    for (let n = 1; n <= count; n++) {
        const index = String(n);
        const year = o_level_other_years.value?.[index];
        const sitting = o_level_other_sittings.value?.[index]?.value;
        const differsFromPrimary =
            (primaryYear && year && year !== primaryYear) || (primarySitting && sitting && sitting !== primarySitting);
        resitData[index] = Boolean(differsFromPrimary);
    }
};

onMounted(() => {
    ensureObjectRef(o_level_other_years, {});
    listGrades();
    listSubjects();

    watchEffect(() => {
        if (!subjectsLoading.value && subjects.value?.length) {
            populateCurrentDataFromApplication();
            inferResitRowsFromApplication();
        }
    });
});

const populateCurrentDataFromApplication = () => {
    const oLevelResults = application?.relationships?.oLevelResults ?? [];
    const subjectsList = otherSubjects.value as Subject[] | undefined;
    if (!subjectsList?.length || !oLevelResults.length) return;

    const count = Number(requirements.value?.attributes?.otherSubjectsCount) || 0;
    if (!count) return;

    const matchedSubjects = subjectsList
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

    matchedSubjects.slice(0, count).forEach((subj, index) => {
        const rowIndex = String(index + 1);
        const subjectData = ensureObjectRef(o_level_other_subject_ids, {});
        subjectData[rowIndex] = {
            value: subj.subjectId,
            label: subj.label,
        };

        const yearData = ensureObjectRef(o_level_other_years, {});
        yearData[rowIndex] = subj.examYear;

        const sittingData = ensureObjectRef(o_level_other_sittings, {});
        const sittingLabel = EXAM_SITTINGS.find((sitting) => sitting.value === subj.examSitting)?.label ?? subj.examSitting;
        sittingData[rowIndex] = {
            value: subj.examSitting,
            label: sittingLabel,
        };

        const gradeData = ensureObjectRef(o_level_other_grade_ids, {});
        gradeData[rowIndex] = subj.gradeId;
    });
};
</script>

<template>
    <HeadingSmall
        :title="`${$t('trans.o_level_other_subjects')} (${requirements?.attributes?.otherSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />
    <template v-if="requirements?.attributes && Number(requirements?.attributes?.otherSubjectsCount) > 0">
        <div class="my-6 flex flex-col space-y-3">
            <div
                class="overflow-hidden rounded-lg border border-border bg-card shadow-sm"
                v-for="n in requirements?.attributes?.otherSubjectsCount"
                :key="`mobile_other${n}`"
            >
                <div class="border-b border-border bg-muted/50 px-4 py-2">
                    <h3 class="text-xs font-semibold uppercase text-accent-foreground">{{ `${$tChoice('trans.subject', 1)} ${n}` }}</h3>
                </div>
                <div class="space-y-3 p-4">
                    <div v-if="!isViewOnly" class="flex items-center">
                        <BaseCheckbox
                            :input-id="`other_resit_${n}`"
                            :model-value="Boolean(o_level_other_resit_rows?.[String(n)])"
                            :label="$t('trans.portal_o_level_resit_toggle')"
                            @update:model-value="(checked: boolean) => onResitToggle(String(n), checked)"
                        />
                    </div>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                        <div class="flex flex-col space-y-1">
                            <ItemLabel :label="$tChoice('trans.subject', 1)" />
                            <SpinnerComponent class="flex items-center justify-center" v-if="subjectsLoading || gradesLoading" />
                            <div class="flex w-full" v-else>
                                <SelectOtherSubject
                                    :disabled="isViewOnly"
                                    class="flex w-full"
                                    :options="options"
                                    :input-id="`other_subject_${n}`"
                                    :model-value="o_level_other_subject_ids?.[n?.toString() ?? ''] || null"
                                    @update:model-value="(option: SelectOption) => onSubjectChange(option, n.toString() ?? '')"
                                />
                            </div>
                        </div>
                        <template v-if="isViewOnly || isResitRow(String(n))">
                            <div class="flex flex-col space-y-1">
                                <ItemLabel :label="$tChoice('trans.year', 1)" />
                                <div class="flex w-full">
                                    <SelectExamYear
                                        :disabled="isViewOnly"
                                        :input-id="`other_year_${n}`"
                                        :date-of-birth="examDateOfBirth"
                                        v-model="o_level_other_years![String(n)]"
                                    />
                                </div>
                            </div>
                            <div class="flex flex-col space-y-1">
                                <ItemLabel :label="$tChoice('trans.sitting', 1)" />
                                <div class="flex w-full">
                                    <SelectSitting
                                        :disabled="isViewOnly"
                                        class="flex w-full"
                                        :model-value="o_level_other_sittings?.[n.toString() ?? ''] || null"
                                        @update:modelValue="(option: SelectOption) => onSittingChange(option, n.toString() ?? '')"
                                    />
                                </div>
                            </div>
                        </template>
                        <div class="flex flex-col space-y-1" :class="{ 'md:col-span-2': !isViewOnly && !isResitRow(String(n)) }">
                            <ItemLabel :label="$tChoice('trans.grade', 1)" />
                            <SpinnerComponent class="flex w-full items-center justify-center" v-if="gradesLoading || subjectsLoading" />
                            <template v-else>
                                <OLevelGradeButtons
                                    :options="getOptionsForSubject(n.toString() ?? '')"
                                    :selected-grade-id="getSelectedGradeId(n.toString() ?? '')"
                                    :disabled="isViewOnly"
                                    @select="(gradeId) => selectGrade(n.toString() ?? '', gradeId)"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <template v-else>
        <Empty :message="$t('trans.no_other_subjects_found')" />
    </template>
</template>

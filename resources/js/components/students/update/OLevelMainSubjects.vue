<script setup lang="ts">
import { BaseCheckbox } from '@/components/core/form';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import ItemLabel from '@/components/students/update/mobile/ItemLabel.vue';
import OLevelGradeButtons from '@/components/students/update/OLevelGradeButtons.vue';
import SelectExamYear from '@/components/students/update/SelectExamYear.vue';
import SelectSitting from '@/components/students/update/SelectSitting.vue';
import { useGrades } from '@/composables/institution/useGrades';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { EXAM_SITTINGS } from '@/lib/constants';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { AcademicOLevelResult, Enrolment } from '@/types/enrolments';
import { RadioGroupOption } from '@/types/forms';
import { Grade, Subject } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { storeToRefs } from 'pinia';
import { computed, onMounted, Ref, watch, watchEffect } from 'vue';

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
    o_level_subject_ids,
    o_level_years,
    o_level_sittings,
    o_level_primary_year,
    o_level_primary_sitting,
    o_level_resit_subjects,
    levelRequirements,
    courseRequirements,
} = storeToRefs(store);
const examDateOfBirth = computed(() => (isEditing ? null : useCreateApplicationFormStore().date_of_birth));
const { listGrades, isLoading, grades } = useGrades();
const { getMainSittingYear, getMainSitting } = useStudentPortal();

function ensureObjectRef<T extends object>(refObj: Ref<T | undefined | null> | undefined, defaultValue: T): T {
    if (!refObj) throw new Error('Ref is undefined');
    if (!refObj.value) {
        refObj.value = defaultValue;
    }
    return refObj.value;
}

const requirements = computed(() => {
    if (courseRequirements && courseRequirements.value && Number(String(courseRequirements.value?.id)) > 0) {
        return courseRequirements.value;
    }
    if (levelRequirements && levelRequirements.value && Number(String(levelRequirements.value?.id)) > 0) {
        return levelRequirements.value;
    }
    return null;
});

const mainSubjectIds = computed(() =>
    (requirements.value?.relationships?.subjects ?? []).map((subject: Subject) => String(subject.id)),
);

const isResitSubject = (subjectId: string): boolean => {
    return Boolean(o_level_resit_subjects.value?.[subjectId]);
};

const propagatePrimaryToSubject = (subjectId: string) => {
    if (isResitSubject(subjectId)) {
        return;
    }
    const yearData = ensureObjectRef(o_level_years, {});
    const sittingData = ensureObjectRef(o_level_sittings, {});
    if (o_level_primary_year.value) {
        yearData[subjectId] = o_level_primary_year.value;
    }
    if (o_level_primary_sitting.value) {
        sittingData[subjectId] = o_level_primary_sitting.value;
    }
};

const propagatePrimaryToAll = () => {
    mainSubjectIds.value.forEach((subjectId) => propagatePrimaryToSubject(subjectId));
};

const onPrimarySittingChange = (value: SelectOption | null) => {
    o_level_primary_sitting.value = value;
    propagatePrimaryToAll();
};

const onResitToggle = (subjectId: string, checked: boolean) => {
    const resitData = ensureObjectRef(o_level_resit_subjects, {});
    resitData[subjectId] = checked;
    if (!checked) {
        propagatePrimaryToSubject(subjectId);
    }
};

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
        if (!isResitSubject(subjectId)) {
            propagatePrimaryToSubject(subjectId);
        }
    }
};

const onSittingChange = (value: SelectOption | null, subjectId: string) => {
    const sittingData = ensureObjectRef(o_level_sittings, {});
    sittingData[subjectId] = value ?? ({} as SelectOption);
};

const getOptionsForSubject = (subject: Subject): RadioGroupOption[] => {
    if (!subject || !grades.value) return [];

    return grades.value
        .filter((grade: Grade) => Number(grade.attributes.position) < 4)
        .map((grade: Grade) => ({
            value: `${subject.id}|${grade.id}`,
            label: grade.attributes?.name,
            inputId: `radio_${subject.id}_${grade.id}`,
        }));
};

const getSelectedGradeId = (subject: Subject): string | null => {
    const subjectId = subject?.id?.toString() ?? '';
    return o_level_subject_ids?.value?.[subjectId] ?? null;
};

const selectGrade = (subject: Subject, gradeId: string) => {
    onRadioChange(`${subject.id}|${gradeId}`);
};

const syncPrimaryFromSubjects = () => {
    if (o_level_years.value) {
        const sharedYear = getMainSittingYear(o_level_years.value);
        if (sharedYear && !o_level_primary_year.value) {
            o_level_primary_year.value = sharedYear;
        }
    }
    if (o_level_sittings.value) {
        const sharedSitting = getMainSitting(o_level_sittings.value);
        if (sharedSitting && !o_level_primary_sitting.value) {
            o_level_primary_sitting.value = sharedSitting;
        }
    }
};

const inferResitFlagsFromApplication = () => {
    const subjects = requirements.value?.relationships?.subjects as Subject[] | undefined;
    if (!subjects?.length || !o_level_years.value || !o_level_sittings.value) {
        return;
    }
    const primaryYear = o_level_primary_year.value;
    const primarySitting = o_level_primary_sitting.value?.value;
    const resitData = ensureObjectRef(o_level_resit_subjects, {});

    subjects.forEach((subject) => {
        const subjectId = String(subject.id);
        const year = o_level_years.value?.[subjectId];
        const sitting = o_level_sittings.value?.[subjectId]?.value;
        const differsFromPrimary =
            (primaryYear && year && year !== primaryYear) || (primarySitting && sitting && sitting !== primarySitting);
        resitData[subjectId] = Boolean(differsFromPrimary);
    });
};

onMounted(async () => {
    ensureObjectRef(o_level_years, {});
    await listGrades();

    watchEffect(() => {
        if (!isLoading.value && grades.value?.length) {
            populateCurrentDataFromApplication();
            syncPrimaryFromSubjects();
            inferResitFlagsFromApplication();
        }
    });
});

watch(
    () => mainSubjectIds.value.join(','),
    () => {
        if (o_level_primary_year.value || o_level_primary_sitting.value) {
            propagatePrimaryToAll();
        }
    },
);

watch(o_level_primary_year, () => {
    propagatePrimaryToAll();
});

const populateCurrentDataFromApplication = () => {
    if (application?.relationships?.oLevelResults?.length) {
        const subjects = requirements.value?.relationships?.subjects as Subject[] | undefined;
        if (!subjects?.length) return;
        subjects.forEach((subject) => {
            const subjectId = subject.id?.toString();
            if (!subjectId) return;
            const result = application?.relationships?.oLevelResults?.find(
                (r: AcademicOLevelResult) => r.attributes.subjectId.toString() === subjectId,
            );
            const yearData = ensureObjectRef(o_level_years, {});
            if (result) {
                yearData[subjectId] = String(result.attributes.examYear);
            } else {
                delete yearData[subjectId];
            }

            const sittingData = ensureObjectRef(o_level_sittings, {});
            const sittingLabel =
                EXAM_SITTINGS.find((sitting) => sitting.value === result?.attributes?.examSitting)?.label ?? result?.attributes?.examSitting;
            if (result) {
                sittingData[subjectId] = { value: result?.attributes?.examSitting, label: String(sittingLabel ?? '') };
            }

            const subjectData = ensureObjectRef(o_level_subject_ids, {});
            if (result) {
                subjectData[subjectId] = String(result?.attributes?.gradeId);
            }
        });
    }
};
</script>

<template>
    <HeadingSmall
        :title="`${$t('trans.o_level_main_subjects')} (${requirements?.attributes?.mainSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />
    <template v-if="requirements?.relationships?.subjects && requirements.relationships.subjects.length > 0">
        <div v-if="!isViewOnly" class="my-4 rounded-lg border border-border bg-muted/30 p-4">
            <HeadingSmall
                :title="$t('trans.portal_o_level_primary_examination')"
                :description="$t('trans.portal_o_level_primary_examination_description')"
            />
            <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="flex flex-col space-y-1">
                    <ItemLabel :label="$t('trans.portal_o_level_primary_year')" />
                    <SelectExamYear
                        :input-id="'primary_exam_year'"
                        :date-of-birth="examDateOfBirth"
                        v-model="o_level_primary_year"
                    />
                </div>
                <div class="flex flex-col space-y-1">
                    <ItemLabel :label="$t('trans.portal_o_level_primary_sitting')" />
                    <SelectSitting
                        class="flex w-full"
                        :model-value="o_level_primary_sitting"
                        @update:modelValue="onPrimarySittingChange"
                    />
                </div>
            </div>
        </div>
        <div class="my-6 flex flex-col space-y-3">
            <div
                class="overflow-hidden rounded-lg border border-border bg-card shadow-sm"
                v-for="subject in requirements.relationships.subjects"
                :key="`mobile_${subject?.id ?? ''}`"
            >
                <div class="border-b border-border bg-muted/50 px-4 py-2">
                    <h3 class="text-xs font-semibold uppercase text-accent-foreground">{{ subject?.attributes?.name }}</h3>
                </div>
                <div class="space-y-3 p-4">
                    <div v-if="!isViewOnly" class="flex items-center">
                        <BaseCheckbox
                            :input-id="`resit_${subject.id}`"
                            :model-value="Boolean(o_level_resit_subjects?.[String(subject.id)])"
                            :label="$t('trans.portal_o_level_resit_toggle')"
                            @update:model-value="(checked: boolean) => onResitToggle(String(subject.id), checked)"
                        />
                    </div>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <template v-if="isViewOnly || isResitSubject(String(subject.id))">
                            <div class="flex flex-col space-y-1">
                                <ItemLabel :label="$tChoice('trans.year', 1)" />
                                <SelectExamYear
                                    :disabled="isViewOnly"
                                    :input-id="`year_${subject.id}`"
                                    :date-of-birth="examDateOfBirth"
                                    v-model="o_level_years![String(subject.id)]"
                                />
                            </div>
                            <div class="flex flex-col space-y-1">
                                <ItemLabel :label="$tChoice('trans.sitting', 1)" />
                                <div class="flex w-full">
                                    <SelectSitting
                                        :disabled="isViewOnly"
                                        class="flex w-full"
                                        :model-value="o_level_sittings?.[subject?.id?.toString() ?? ''] || null"
                                        @update:modelValue="(option: SelectOption) => onSittingChange(option, String(subject.id))"
                                    />
                                </div>
                            </div>
                        </template>
                        <div class="flex flex-col space-y-1 sm:col-span-2">
                            <ItemLabel :label="$tChoice('trans.grade', 1)" />
                            <SpinnerComponent class="flex w-full items-center justify-center" v-if="isLoading" />
                            <template v-else>
                                <OLevelGradeButtons
                                    :options="getOptionsForSubject(subject)"
                                    :selected-grade-id="getSelectedGradeId(subject)"
                                    :disabled="isViewOnly"
                                    @select="(gradeId) => selectGrade(subject, gradeId)"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <template v-else>
        <Empty :message="$t('trans.no_main_subjects_found')" />
    </template>
</template>

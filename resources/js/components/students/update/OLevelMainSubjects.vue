<script setup lang="ts">
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import ItemLabel from '@/components/students/update/mobile/ItemLabel.vue';
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
import { storeToRefs } from 'pinia';
import { computed, onMounted, ref, Ref, watchEffect } from 'vue';

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
const { o_level_subject_ids, o_level_years, o_level_sittings, levelRequirements, courseRequirements } = storeToRefs(store);
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

    return grades.value
        .filter((grade: Grade) => Number(grade.attributes.position) < 4)
        .map((grade: Grade) => ({
            value: `${subject.id}|${grade.id}`,
            label: grade.attributes?.name,
            inputId: `radio_${subject.id}_${grade.id}`,
        }));
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
const mainYear = ref<string | null>(null);
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
        const subjects = requirements?.value?.relationships?.subjects as Subject[] | undefined;
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
        :title="`${$t('trans.o_level_main_subjects')} (${requirements?.attributes?.mainSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />
    <template v-if="requirements?.relationships?.subjects && requirements.relationships.subjects.length > 0">
        <div class="my-6 flex flex-col space-y-3">
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow"
                v-for="subject in requirements.relationships.subjects"
                :key="`mobile_${subject?.id ?? ''}`"
            >
                <div class="bg-card border-b border-gray-100 px-4 py-2">
                    <h3 class="text-accent-foreground text-xs font-semibold uppercase">{{ subject?.attributes?.name }}</h3>
                </div>
                <div class="space-y-3 p-4">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="flex flex-col space-y-1">
                            <ItemLabel :label="$tChoice('trans.year', 1)" />
                            <SelectYear
                                :disabled="isViewOnly"
                                :input-id="`year_${subject.id}`"
                                :model-value="o_level_years?.[subject?.id?.toString() ?? ''] || null"
                                @update:model-value="(value) => onYearChange(value, subject?.id ?? '')"
                            />
                        </div>
                        <div class="flex flex-col space-y-1">
                            <ItemLabel :label="$tChoice('trans.sitting', 1)" />
                            <div class="flex w-full">
                                <SelectSitting
                                    :disabled="isViewOnly"
                                    class="flex w-full"
                                    :model-value="o_level_sittings?.[subject?.id?.toString() ?? ''] || null"
                                    @update:modelValue="(option: SelectOption) => onSittingChange(option, subject?.id ?? '')"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col space-y-1">
                            <ItemLabel :label="$tChoice('trans.grade', 1)" />
                            <SpinnerComponent class="flex w-full items-center justify-center" v-if="isLoading" />
                            <template v-else>
                                <BaseRadioGroup
                                    :disabled="isViewOnly"
                                    class="flex items-center"
                                    :options="getOptionsForSubject(subject)"
                                    :default-value="getDefaultOLevels(subject)"
                                    :label-uppercase="true"
                                    :is-required="true"
                                    orientation="horizontal"
                                    @update:modelValue="onRadioChange"
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

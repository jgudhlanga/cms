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
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { DepartmentLevelRequirement } from '@/types/department-meta-data';
import { RadioGroupOption } from '@/types/forms';
import { Grade, Subject } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { storeToRefs } from 'pinia';
import { computed, onMounted, Ref } from 'vue';

interface Props {
    levelRequirements?: DepartmentLevelRequirement | null;
}

defineProps<Props>();

const { listSubjects, isLoading: subjectsLoading, subjects } = useSubjects();
const { listGrades, isLoading: gradesLoading, grades } = useGrades();
const { o_level_other_subject_ids, o_level_other_grade_ids, o_level_other_years, o_level_other_sittings } =
    storeToRefs(useCreateApplicationFormStore());

function ensureObjectRef<T extends object>(refObj: Ref<T | undefined | null> | undefined, defaultValue: T): T {
    if (!refObj) throw new Error('Ref is undefined');
    if (!refObj.value) {
        refObj.value = defaultValue;
    }
    return refObj.value;
}

const options = computed(() => {
    return subjects.value.map(
        (subject: Subject) =>
            <SelectOption>{
                value: Number(subject.id),
                label: subject?.attributes?.name,
            },
    );
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

const onSubjectChange = (value: string, index: string) => {
    if (!o_level_other_subject_ids) {
        return;
    }
    if (!o_level_other_subject_ids.value) {
        o_level_other_subject_ids.value = {};
    }
    o_level_other_subject_ids.value[index] = value;
};
const onSittingChange = (value: string, index: string) => {
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
    return grades.value.map((grade: Grade) => ({
        value: `${index}|${grade.id}`,
        label: grade.attributes?.name,
        inputId: `radio_${index}_${grade.id}`,
    }));
};

onMounted(() => {
    listGrades();
    listSubjects();
});

const getDefaultOLevels = (index: string) => {
    if (!index || !o_level_other_subject_ids?.value || !grades.value) return null;
    const gradeId = o_level_other_subject_ids.value[index ?? ''];
    if (!gradeId) return null;
    const grade = grades.value.find((g: Grade) => g.id == gradeId);
    if (!grade) return null;
    return `${index}|${gradeId}`;
};
</script>

<template>
    <HeadingSmall
        :title="`${$t('trans.o_level_other_subjects')} (${levelRequirements?.attributes?.otherSubjectsCount})`"
        :description="$t('trans.o_level_results_description')"
    />
    <template v-if="levelRequirements?.attributes && Number(levelRequirements?.attributes?.otherSubjectsCount) > 0">
        <div class="flex w-full flex-col overflow-auto">
            <table class="hava-table my-4">
                <thead class="hava-thead">
                    <tr>
                        <th class="hava-th" align="left">{{ $tChoice('trans.subject', 1) }}</th>
                        <th class="hava-th" align="center">{{ $tChoice('trans.year', 1) }}</th>
                        <th class="hava-th">{{ $tChoice('trans.sitting', 1) }}</th>
                        <th class="hava-th" align="center">{{ $tChoice('trans.grade', 1) }}</th>
                    </tr>
                </thead>
                <tbody class="hava-tbody">
                    <tr class="hava-tr" v-for="n in levelRequirements?.attributes?.otherSubjectsCount" :key="n">
                        <td class="hava-td" align="left">
                            <SpinnerComponent class="flex items-center justify-center" v-if="subjectsLoading || gradesLoading" />
                            <SelectOtherSubject
                                v-else
                                :options="options"
                                :input-id="`other_subject_${n}`"
                                :model-value="o_level_other_subject_ids?.[n?.toString() ?? ''] || null"
                                @update:model-value="(value: string) => onSubjectChange(value, n.toString() ?? '')"
                            />
                        </td>
                        <td class="hava-td" align="center">
                            <SelectYear
                                :input-id="`other_year_${n}`"
                                :model-value="o_level_other_years?.[n?.toString() ?? ''] || null"
                                @update:model-value="(value: string) => onYearChange(value, n.toString() ?? '')"
                            />
                        </td>
                        <td class="hava-td">
                            <SelectSitting
                                :model-value="o_level_other_sittings?.[n.toString() ?? ''] || null"
                                @update:modelValue="(value) => onSittingChange(value, n.toString() ?? '')"
                            />
                        </td>
                        <td class="hava-td" align="center">
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

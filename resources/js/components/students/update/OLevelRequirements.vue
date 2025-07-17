<script setup lang="ts">
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import Empty from '@/components/core/util/Empty.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import SelectSitting from '@/components/students/update/SelectSitting.vue';
import SelectYear from '@/components/students/update/SelectYear.vue';
import { useGrades } from '@/composables/institution/useGrades';
import { useSubjects } from '@/composables/institution/useSubjects';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { DepartmentLevelRequirement } from '@/types/department-meta-data';
import { RadioGroupOption } from '@/types/forms';
import { Grade, Subject } from '@/types/institution';
import { trans } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { onMounted } from 'vue';

interface Props {
    isViewOnly?: boolean;
    levelRequirements?: DepartmentLevelRequirement | null;
}

const props = withDefaults(defineProps<Props>(), {
    isViewOnly: false,
});
const { levelRequirements } = props;
const { listGrades, isLoading: gradesLoading, grades } = useGrades();
const { listSubjects, isLoading: subjectLoading } = useSubjects();
const { o_level_subject_ids, o_level_years, o_level_sittings } = storeToRefs(useCreateApplicationFormStore());
const onRadioChange = (value: string) => {
    const [subjectId, gradeId] = value.split('|');
    if (!subjectId || !gradeId) return;
    if (!o_level_subject_ids) {
        return;
    }
    // Ensure it's initialized
    if (!o_level_subject_ids.value) {
        o_level_subject_ids.value = {}; // initialize as an empty object
    }
    // Update the reactive object
    if (gradeId == 'remove') {
        delete o_level_subject_ids.value[subjectId];
    } else {
        o_level_subject_ids.value[subjectId] = gradeId;
    }
};
const onYearChange = (value: string, subjectId: string) => {
    if (!o_level_years) {
        return;
    }
    // Ensure it's initialized
    if (!o_level_years.value) {
        o_level_years.value = {};
    }
    // Update the reactive object
    o_level_years.value[subjectId] = value;
};

const getOptionsForSubject = (subject: Subject): RadioGroupOption[] => {
    if (!subject || !grades.value) return [];
    const dataOptions = grades.value.map((grade: Grade) => ({
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

onMounted(async () => {
    await listGrades();
    await listSubjects();
});

const getDefaultOLevels = (subject: Subject) => {
    if (!subject || !o_level_subject_ids?.value || !grades.value) return null;
    const gradeId = o_level_subject_ids.value[subject?.id ?? ''];
    if (!gradeId) return null;
    const grade = grades.value.find((g: Grade) => g.id == gradeId);
    if (!grade) return null;
    return `${subject.id}|${gradeId}`;
};

const getGrade = (subject: Subject) => {
    const subjectId = subject?.id;
    if (!subjectId) return '';

    const gradeId = o_level_subject_ids?.value?.[subjectId];
    if (!gradeId) return '';

    const matchedGrade = grades.value?.find((grade) => grade.id == gradeId);
    return matchedGrade?.attributes?.name ?? '';
};
</script>

<template>
    <HeadingSmall :title="$t('trans.o_level_main_subjects')" :description="$t('trans.o_level_results_description')" />
    <template v-if="levelRequirements?.relationships?.subjects && levelRequirements.relationships.subjects.length > 0">
        <div class="flex w-full flex-col overflow-auto">
            <table class="hava-table my-4">
                <thead class="hava-thead">
                    <tr>
                        <th class="hava-th" align="left">{{ $tChoice('trans.subject', 1) }}</th>
                        <th class="hava-th" align="center">{{ $tChoice('trans.year', 1) }}</th>
                        <th class="hava-th" align="center">{{ $tChoice('trans.sitting', 1) }}</th>
                        <th class="hava-th" align="center">{{ $tChoice('trans.grade', 1) }}</th>
                    </tr>
                </thead>
                <tbody class="hava-tbody">
                    <tr class="hava-tr" v-for="subject in levelRequirements.relationships.subjects" :key="subject?.id ?? ''">
                        <td class="hava-td" align="left">{{ subject?.attributes?.name }}</td>
                        <td class="hava-td" align="center">
                            <SelectYear
                                :input-id="`year_${subject.id}`"
                                :model-value="o_level_years?.[subject?.id?.toString() ?? ''] || null"
                                @update:model-value="(value) => onYearChange(value, subject?.id ?? '')"
                            />
                        </td>
                        <td class="hava-td" align="center">
                            <SelectSitting
                                :model-value="o_level_sittings?.[subject?.id?.toString() ?? ''] || null"
                                @change="(value) => {
                                    console.log('change', value);
                                }"
                            />
                        </td>
                        <td class="hava-td" align="center">
                            <SpinnerComponent class="flex items-center justify-center" v-if="gradesLoading || subjectLoading" />
                            <template v-else>
                                <div v-if="!isViewOnly" class="flex w-full items-center justify-center space-x-8">
                                    <BaseRadioGroup
                                        class="flex items-center justify-center"
                                        :options="getOptionsForSubject(subject)"
                                        :default-value="getDefaultOLevels(subject)"
                                        :label-uppercase="true"
                                        :is-required="true"
                                        orientation="horizontal"
                                        @update:modelValue="onRadioChange"
                                    />
                                </div>
                                <template v-else>
                                    <span>{{ getGrade(subject) }}</span>
                                </template>
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
    <HeadingSmall :title="$t('trans.any_other_subjects')" :description="$t('trans.o_level_results_description')" class="mt-4" />
</template>

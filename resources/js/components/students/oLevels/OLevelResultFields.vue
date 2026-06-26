<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import ItemLabel from '@/components/students/update/mobile/ItemLabel.vue';
import OLevelGradeButtons from '@/components/students/update/OLevelGradeButtons.vue';
import SelectExamYear from '@/components/students/update/SelectExamYear.vue';
import SelectSitting from '@/components/students/update/SelectSitting.vue';
import { RadioGroupOption } from '@/types/forms';
import { SelectOption } from '@/types/utils';

interface FieldErrors {
    exam_year?: string;
    exam_sitting?: string;
    grade_id?: string;
}

interface Props {
    subjectId: string;
    gradeOptions: RadioGroupOption[];
    errors?: FieldErrors;
    disabled?: boolean;
    dateOfBirth?: string | null;
    isLoading?: boolean;
}

withDefaults(defineProps<Props>(), {
    errors: () => ({}),
    disabled: false,
    dateOfBirth: null,
    isLoading: false,
});

const examYear = defineModel<string | null>('examYear');
const examSitting = defineModel<SelectOption | null>('examSitting');
const gradeId = defineModel<string | null>('gradeId');
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex flex-col space-y-1">
                <ItemLabel :label="$tChoice('trans.year', 1)" />
                <SelectExamYear
                    :input-id="`year_${subjectId}`"
                    :date-of-birth="dateOfBirth"
                    :disabled="disabled"
                    v-model="examYear"
                />
                <InputError v-if="errors.exam_year" :message="errors.exam_year" class="mt-1 flex w-full lowercase" />
            </div>
            <div class="flex flex-col space-y-1">
                <ItemLabel :label="$tChoice('trans.sitting', 1)" />
                <SelectSitting class="flex w-full" :disabled="disabled" v-model="examSitting" />
                <InputError v-if="errors.exam_sitting" :message="errors.exam_sitting" class="mt-1 flex w-full lowercase" />
            </div>
        </div>
        <div class="flex flex-col space-y-1">
            <ItemLabel :label="$tChoice('trans.grade', 1)" />
            <SpinnerComponent v-if="isLoading" class="flex w-full items-center justify-center" />
            <OLevelGradeButtons
                v-else
                :options="gradeOptions"
                :selected-grade-id="gradeId"
                :disabled="disabled"
                @select="(id) => (gradeId = id)"
            />
            <InputError v-if="errors.grade_id" :message="errors.grade_id" class="mt-1 flex w-full lowercase" />
        </div>
    </div>
</template>

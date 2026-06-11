<script setup lang="ts">
import SubjectComboSelect from '@/components/core/form/combobox/SubjectComboSelect.vue';
import InputError from '@/components/core/form/InputError.vue';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import SpinnerComponent from '@/components/core/loader/SpinnerComponent.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import SelectSitting from '@/components/students/update/SelectSitting.vue';
import SelectYear from '@/components/students/update/SelectYear.vue';
import { Label } from '@/components/ui/label';
import { useOLevelResults } from '@/composables/students/useOLevelResults';
import { closeModal, errorAlert, getModalEdit, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS, EXAM_SITTINGS } from '@/lib/constants';
import { validateSelectOption } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { OLevelSubjectResult, OLevelSubjectResultParams } from '@/types/enrolments';
import { RadioGroupOption } from '@/types/forms';
import { Grade } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { z } from 'zod';

const form = useForm<OLevelSubjectResultParams>({
    subject_id: '',
    exam_year: '',
    exam_sitting: '',
    grade_id: '',
});

interface SubjectErrors {
    [key: string]: string;
}

const examSittings = ref(EXAM_SITTINGS);
const result = ref<OLevelSubjectResult>();
const subjectErrors = ref<Record<string, string>>({});
const subjectOption = ref<SelectOption | null>(null);
const sittingOption = ref<SelectOption | null>(null);

const { modals } = useModalStore();
const { isLoading, grades } = useOLevelResults();

// Schema
// Updated Schema - better string handling
const SubjectResultSchema = z.object({
    exam_year: z
        .union([z.string(), z.number()])
        .refine((v) => {
            // Convert to string first, then to number for validation
            const yearStr = String(v).trim();
            if (yearStr === '') return false;

            const year = Number(yearStr);
            return !isNaN(year) && year > 1900 && year <= new Date().getFullYear();
        }, 'Year must be between 1900 and current year')
        .transform((v) => String(v)), // Ensure it's always a string after validation
    exam_sitting: z.any().refine(validateSelectOption, 'Exam sitting is required'),
    grade_id: z.string().min(1, 'Grade is required'),
});

// Helper functions
const getExamSitting = (key: string) => {
    const sitting = examSittings.value.find((s) => String(s.value) === key);
    return sitting ? String(sitting.label) : '---';
};

const getOptionsForSubject = (): RadioGroupOption[] => {
    if (!grades.value) return [];

    return grades.value
        .filter((grade: Grade) => Number(grade.attributes.position) < 4)
        .map((grade: Grade) => ({
            value: `${grade.id}`,
            label: grade.attributes?.name,
            inputId: `radio_${grade.id}`,
        }));
};

// Validation helper - FIXED: uses form data directly
const validateSubjectForm = (): SubjectErrors => {
    const formData = {
        subject_id: String(subjectOption.value?.value),
        exam_year: form.exam_year,
        exam_sitting: String(sittingOption.value?.value),
        grade_id: form.grade_id,
    };
    const parsed = SubjectResultSchema.safeParse(formData);
    const errors: SubjectErrors = {};

    if (!parsed.success) {
        parsed.error.errors.forEach((err) => {
            const path = err.path[0]?.toString() || '_form';
            errors[path] = err.message;
        });
    }

    return errors;
};

watch(modals!, () => {
    result.value = getModalEdit(APP_MODULE_KEYS.o_level_subjects);

    if (result.value) {
        form.subject_id = String(result.value?.id ?? '');
        form.exam_year = String(result.value?.attributes?.examYear ?? '');
        form.exam_sitting = String(result.value?.attributes?.examSitting ?? '');
        form.grade_id = String(result.value?.attributes?.gradeId ?? '');

        subjectOption.value = {
            label: result.value?.attributes?.subject ?? '',
            value: String(result.value?.id ?? ''),
        };

        sittingOption.value = {
            label: getExamSitting(String(result.value?.attributes?.examSitting)),
            value: String(result.value?.attributes?.examSitting ?? ''),
        };
    }

    form.defaults();
});

const emit = defineEmits(['saved']);

const saveSubjectResult = () => {
    // Reset errors
    subjectErrors.value = {};
    // Validate form data
    const validationErrors = validateSubjectForm();

    if (Object.keys(validationErrors).length > 0) {
        subjectErrors.value = validationErrors;
        return;
    }

    const payload = {
        subject_id: String(subjectOption.value?.value),
        exam_year: String(form.exam_year),
        exam_sitting: String(sittingOption.value?.value),
        grade_id: form.grade_id,
    };

    try {
        Object.assign(form, payload);
        if (Number(result?.value?.attributes?.resultId) > 0) {
            handleUpdate(String(result?.value?.attributes?.resultId));
        } else {
            handleCreate();
        }
    } catch {
        errorAlert('An unexpected error occurred.');
    }
};

const handleCreate = () => {
    form.post(route('portal.store-o-level-results', String(result.value?.attributes?.studentId)), {
        preserveScroll: true,
        onSuccess: () => {
            successAlert('Result successfully created');
            subjectErrors.value = {};
            form.reset();
            emit('saved');
            closeModal(APP_MODULE_KEYS.o_level_subjects);
        },
        onError: (errors: any) => {
            handleSubmissionErrors(errors);
        },
    });
};

const handleUpdate = (resultId: string) => {
    form.put(route('portal.update-o-level-results', resultId), {
        preserveScroll: true,
        onSuccess: () => {
            successAlert('Result successfully created');
            subjectErrors.value = {};
            form.reset();
            emit('saved');
            closeModal(APP_MODULE_KEYS.o_level_subjects);
        },
        onError: (errors: any) => {
            handleSubmissionErrors(errors);
        },
    });
};

const handleSubmissionErrors = (errors: any): void => {
    if (!errors) {
        errorAlert('An unexpected error occurred.');
        return;
    }

    const newErrors: SubjectErrors = {};

    Object.entries(errors).forEach(([key, value]) => {
        newErrors[key] = Array.isArray(value) ? value.join(', ') : String(value);
    });

    subjectErrors.value = newErrors;
    errorAlert(Object.values(newErrors).join('\n'));
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.o_level_subjects"
        :title="`${result ? $t('trans.create') : $t('trans.create')} O Level Result`"
        :on-form-action="() => saveSubjectResult()"
        :form="form"
    >
        <template #body>
            <div class="flex flex-col">
                <SubjectComboSelect :form="form" :is-required="true" v-model="subjectOption" />
                <InputError v-if="subjectErrors.subject_id" :message="subjectErrors.subject_id" class="mt-1 flex w-full lowercase" />
            </div>
            `
            <div class="">
                <Label>{{ $tChoice('trans.year', 1) }}<RequiredIndicator /></Label>
                <SelectYear input-id="exam_year" v-model="form.exam_year" />
                <InputError v-if="subjectErrors.exam_year" :message="subjectErrors.exam_year" class="mt-1 flex w-full lowercase" />
            </div>
            <div class="flex flex-col space-y-3">
                <Label>{{ $tChoice('trans.sitting', 1) }}<RequiredIndicator /></Label>
                <SelectSitting class="flex w-full" v-model="sittingOption" />
                <InputError v-if="subjectErrors.exam_sitting" :message="subjectErrors.exam_sitting" class="mt-1 flex w-full lowercase" />
            </div>
            <div class="flex flex-col space-y-3">
                <Label>{{ $tChoice('trans.grade', 1) }}<RequiredIndicator /></Label>
                <SpinnerComponent class="flex w-full items-center justify-center" v-if="isLoading" />
                <BaseRadioGroup
                    v-else
                    class="flex items-center"
                    :options="getOptionsForSubject()"
                    :default-value="String(form.grade_id)"
                    @update:modelValue="(value) => (form.grade_id = value)"
                    :label-uppercase="true"
                    :is-required="true"
                    orientation="horizontal"
                    :vertical-layout="false"
                />
                <InputError v-if="subjectErrors.grade_id" :message="subjectErrors.grade_id" class="mt-1 flex w-full lowercase" />
            </div>
        </template>
    </BaseModal>
</template>

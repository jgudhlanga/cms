<script setup lang="ts">
import SubjectComboSelect from '@/components/core/form/combobox/SubjectComboSelect.vue';
import InputError from '@/components/core/form/InputError.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import OLevelResultFields from '@/components/students/oLevels/OLevelResultFields.vue';
import ItemLabel from '@/components/students/update/mobile/ItemLabel.vue';
import { useOLevelResults } from '@/composables/students/useOLevelResults';
import { SizeVariant } from '@/enums/sizes';
import { closeModal, errorAlert, getModalEdit, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS, EXAM_SITTINGS } from '@/lib/constants';
import { validateSelectOption } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import { OLevelSubjectResult, OLevelSubjectResultParams } from '@/types/enrolments';
import { RadioGroupOption } from '@/types/forms';
import { Grade } from '@/types/institution';
import { SelectOption } from '@/types/utils';
import { useMediaQuery } from '@vueuse/core';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';
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
const isMobile = useMediaQuery('(max-width: 639px)');

const SubjectResultSchema = z.object({
    exam_year: z
        .union([z.string(), z.number()])
        .refine((v) => {
            const yearStr = String(v).trim();
            if (yearStr === '') return false;

            const year = Number(yearStr);
            return !isNaN(year) && year > 1900 && year <= new Date().getFullYear();
        }, 'Year must be between 1900 and current year')
        .transform((v) => String(v)),
    exam_sitting: z.any().refine(validateSelectOption, 'Exam sitting is required'),
    grade_id: z.string().min(1, 'Grade is required'),
});

const subjectId = computed(() => String(result.value?.id ?? 'new'));
const isEditing = computed(() => Number(result.value?.attributes?.resultId) > 0);
const modalTitle = computed(() =>
    isEditing.value ? trans('trans.ui_edit_o_level_result') : trans('trans.ui_add_o_level_result'),
);
const modalSize = computed(() => (isMobile.value ? SizeVariant.full : SizeVariant.sm));

const getExamSitting = (key: string) => {
    const sitting = examSittings.value.find((s) => String(s.value) === key);
    return sitting ? String(sitting.label) : '---';
};

const gradeOptions = computed((): RadioGroupOption[] => {
    if (!grades.value) return [];

    return grades.value
        .filter((grade: Grade) => Number(grade.attributes.position) < 4)
        .map((grade: Grade) => ({
            value: `${subjectId.value}|${grade.id}`,
            label: grade.attributes?.name,
            inputId: `radio_${subjectId.value}_${grade.id}`,
        }));
});

const fieldErrors = computed(() => ({
    exam_year: subjectErrors.value.exam_year,
    exam_sitting: subjectErrors.value.exam_sitting,
    grade_id: subjectErrors.value.grade_id,
}));

const validateSubjectForm = (): SubjectErrors => {
    const formData = {
        subject_id: String(subjectOption.value?.value),
        exam_year: form.exam_year,
        exam_sitting: sittingOption.value,
        grade_id: form.grade_id,
    };
    const parsed = SubjectResultSchema.safeParse(formData);
    const errors: SubjectErrors = {};

    if (!subjectOption.value?.value) {
        errors.subject_id = 'Subject is required';
    }

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
    subjectErrors.value = {};
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
        if (isEditing.value) {
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
            successAlert('Result successfully updated');
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
        :title="modalTitle"
        :size="modalSize"
        :on-form-action="() => saveSubjectResult()"
        :form="form"
        stack-footer-on-mobile
    >
        <template #body>
            <div v-if="isEditing" class="flex flex-col space-y-1">
                <ItemLabel :label="$tChoice('trans.subject', 1)" />
                <p class="text-sm font-medium text-foreground">{{ result?.attributes?.subject }}</p>
            </div>
            <div v-else class="flex flex-col">
                <SubjectComboSelect :form="form" :is-required="true" v-model="subjectOption" />
                <InputError v-if="subjectErrors.subject_id" :message="subjectErrors.subject_id" class="mt-1 flex w-full lowercase" />
            </div>
            <OLevelResultFields
                :subject-id="subjectId"
                :grade-options="gradeOptions"
                :errors="fieldErrors"
                :is-loading="isLoading"
                v-model:exam-year="form.exam_year"
                v-model:exam-sitting="sittingOption"
                v-model:grade-id="form.grade_id"
            />
        </template>
    </BaseModal>
</template>

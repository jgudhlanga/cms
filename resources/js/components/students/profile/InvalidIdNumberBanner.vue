<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { useStudentIdNumberCorrection } from '@/composables/students/useStudentIdNumberCorrection';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { Student } from '@/types/students';
import { Link } from '@inertiajs/vue3';
import { computed, toRef } from 'vue';

const props = defineProps<{
    student: Student;
}>();

const studentRef = toRef(props, 'student');

const {
    draftIdNumber,
    idNumberConflict,
    isDuplicateConflict,
    isIdNumberInvalid,
    isSavingIdNumber,
    onDraftIdNumberUpdate,
    saveIdNumber,
    showInlineEditor,
    suggestedIdNumber,
    useSuggestedIdNumber,
    canManageMaintenance,
} = useStudentIdNumberCorrection(studentRef);

const currentIdNumber = computed(() => props.student?.attributes?.idNumber || '—');

const suggestedDisplay = computed(
    () => suggestedIdNumber.value ?? idNumberConflict.value?.idNumber ?? null,
);
</script>

<template>
    <div
        v-if="isIdNumberInvalid"
        class="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-3 text-sm text-red-950 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100"
        role="alert"
    >
        <p class="font-medium">{{ $t('trans.id_number_invalid_banner_title') }}</p>
        <p class="mt-1 text-xs opacity-90">{{ $t('trans.id_number_invalid_warning') }}</p>
        <p class="mt-2 text-xs font-medium text-red-800 dark:text-red-200">
            {{ $t('trans.id_number_invalid_exams_importance') }}
        </p>

        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <div>
                <p class="text-[0.65rem] font-semibold tracking-widest text-red-800/80 uppercase dark:text-red-200/80">
                    {{ $t('trans.maintenance_faulty_data_current_id') }}
                </p>
                <p class="mt-0.5 font-mono text-sm font-semibold text-red-700 dark:text-red-300">
                    {{ currentIdNumber }}
                </p>
            </div>
            <div v-if="suggestedDisplay">
                <p class="text-[0.65rem] font-semibold tracking-widest text-red-800/80 uppercase dark:text-red-200/80">
                    {{ $t('trans.maintenance_faulty_data_new_id') }}
                </p>
                <p class="mt-0.5 font-mono text-sm font-semibold text-red-700 dark:text-red-300">
                    {{ suggestedDisplay }}
                </p>
            </div>
        </div>

        <div
            v-if="isDuplicateConflict"
            class="mt-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
        >
            <p class="text-xs font-medium">{{ $t('trans.id_number_invalid_conflict_it_support') }}</p>
            <Link
                v-if="canManageMaintenance"
                :href="route('maintenance.faulty-student-ids')"
                class="mt-2 inline-flex text-xs font-semibold text-primary underline-offset-2 hover:underline"
            >
                {{ $t('trans.id_number_invalid_conflict_open_maintenance') }}
            </Link>
        </div>

        <div v-else-if="showInlineEditor" class="mt-3 flex flex-col gap-2">
            <p class="text-[0.65rem] font-semibold tracking-widest text-muted-foreground uppercase">
                {{ $t('trans.id_number') }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <Input
                    :model-value="draftIdNumber"
                    name="student_id_number_correction"
                    class="min-w-[140px] max-w-sm flex-1 bg-background"
                    :placeholder="$t('trans.ui_eg_63_1234567n63')"
                    :disabled="isSavingIdNumber"
                    @update:model-value="onDraftIdNumberUpdate"
                />
                <button
                    v-if="suggestedIdNumber && draftIdNumber.trim() !== suggestedIdNumber"
                    type="button"
                    class="shrink-0 cursor-pointer text-xs font-medium text-primary disabled:opacity-50"
                    :disabled="isSavingIdNumber"
                    @click="useSuggestedIdNumber"
                >
                    {{ $t('trans.maintenance_faulty_data_use_suggested') }}
                </button>
                <BaseButton
                    :title="$t('trans.save')"
                    :variant="ColorVariant.danger"
                    :size="ButtonSize.sm"
                    type="button"
                    classes="shrink-0 rounded-full capitalize"
                    :processing="isSavingIdNumber"
                    :disabled="isSavingIdNumber"
                    @click="saveIdNumber"
                />
            </div>
            <p class="text-xs opacity-90">{{ $t('trans.enrollment_invalid_national_id') }}</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import { Input } from '@/components/ui/input';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentIdNumberCorrection } from '@/composables/students/useStudentIdNumberCorrection';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import type { Enrolment } from '@/types/enrolments';
import type { Student } from '@/types/students';
import { Link } from '@inertiajs/vue3';
import { AlertTriangle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    application: Enrolment;
}>();

const { isNativeCitizen } = useUtils();

const studentRef = computed(
    (): Student =>
        ({
            id: props.application.attributes.studentId,
            type: 'student',
            attributes: {
                idNumber: props.application.attributes.idNumber,
                idNumberValid: props.application.attributes.idNumberValid,
                idType: props.application.attributes.idType,
                suggestedIdNumber: props.application.attributes.suggestedIdNumber,
                idNumberRectificationStatus: props.application.attributes.idNumberRectificationStatus,
                idNumberConflict: props.application.attributes.idNumberConflict,
            },
        }) as Student,
);

const {
    canCorrectIdNumber,
    canManageMaintenance,
    draftIdNumber,
    isDuplicateConflict,
    isIdNumberInvalid,
    isSavingIdNumber,
    onDraftIdNumberUpdate,
    saveIdNumber,
    showInlineEditor,
    suggestedIdNumber,
    useSuggestedIdNumber,
} = useStudentIdNumberCorrection(studentRef);

const showBanner = computed(
    () => isNativeCitizen(props.application.attributes.idType ?? '') && isIdNumberInvalid.value,
);
</script>

<template>
    <div
        v-if="showBanner"
        class="flex flex-col gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-red-900 dark:bg-red-950/40"
        role="alert"
    >
        <div class="flex min-w-0 items-start gap-2.5 sm:flex-1">
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-destructive" />
            <p class="text-sm font-medium text-red-950 dark:text-red-100">
                {{ $t('enrolments.invalid_id_banner_message') }}
            </p>
        </div>

        <div
            v-if="isDuplicateConflict"
            class="rounded border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-950 sm:max-w-xs"
        >
            <p>{{ $t('trans.id_number_invalid_conflict_it_support') }}</p>
            <Link
                v-if="canManageMaintenance"
                :href="route('maintenance.faulty-student-ids')"
                class="mt-1 inline-flex font-semibold text-primary underline-offset-2 hover:underline"
            >
                {{ $t('trans.id_number_invalid_conflict_open_maintenance') }}
            </Link>
        </div>

        <div v-else-if="showInlineEditor && canCorrectIdNumber" class="flex shrink-0 flex-wrap items-center gap-2">
            <Input
                :model-value="draftIdNumber"
                name="enrolment_id_number_correction"
                class="h-9 min-w-40 max-w-xs flex-1 bg-background font-mono text-sm"
                :placeholder="$t('trans.ui_eg_63_1234567n63')"
                :disabled="isSavingIdNumber"
                @update:model-value="onDraftIdNumberUpdate"
            />
            <button
                v-if="suggestedIdNumber && draftIdNumber.trim() !== suggestedIdNumber"
                type="button"
                class="shrink-0 cursor-pointer text-xs font-medium text-primary"
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
    </div>
</template>

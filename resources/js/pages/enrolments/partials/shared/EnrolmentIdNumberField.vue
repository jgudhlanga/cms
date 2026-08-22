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
import { computed } from 'vue';

const props = defineProps<{
    application: Enrolment;
    displayOnly?: boolean;
    highlighted?: boolean;
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
</script>

<template>
    <div v-if="isNativeCitizen(application.attributes.idType ?? '')" class="flex flex-col gap-0.5">
        <span class="text-[0.65rem] font-semibold tracking-[0.1em] text-muted-foreground uppercase">
            {{ $tChoice('trans.id_number', 1) }}
        </span>
        <span
            :class="[
                'text-[0.85rem] font-bold tracking-[-0.01em]',
                isIdNumberInvalid ? 'font-mono text-destructive' : 'text-foreground',
                highlighted && 'underline decoration-emerald-600 decoration-2 underline-offset-2',
            ]"
        >
            {{ application.attributes.idNumber ?? '---' }}
        </span>

        <div
            v-if="isIdNumberInvalid && !displayOnly"
            class="mt-2 rounded-md border border-red-200 bg-red-50 px-2.5 py-2 text-xs text-red-950 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100"
            role="alert"
        >
            <p class="font-medium">{{ $t('trans.id_number_invalid_banner_title') }}</p>
            <p v-if="suggestedIdNumber" class="mt-1 font-mono text-destructive">
                {{ $t('trans.maintenance_faulty_data_new_id') }}: {{ suggestedIdNumber }}
            </p>

            <div
                v-if="isDuplicateConflict"
                class="mt-2 rounded border border-amber-300 bg-amber-50 px-2 py-1.5 text-amber-950"
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

            <div v-else-if="showInlineEditor && canCorrectIdNumber" class="mt-2 flex flex-wrap items-center gap-2">
                <Input
                    :model-value="draftIdNumber"
                    name="enrolment_id_number_correction"
                    class="min-w-[120px] max-w-xs flex-1 bg-background text-sm"
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
    </div>
</template>

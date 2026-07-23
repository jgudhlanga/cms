<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import FaultyStudentIdCorrectionCell from '@/pages/maintenance/partials/students/FaultyStudentIdCorrectionCell.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { isValidZimbabweanIdNumber } from '@/lib/zimbabweanId';
import type { ApprenticeImportPreviewRow } from '@/types/apprentice-import';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const props = defineProps<{
    row: ApprenticeImportPreviewRow;
    saving?: boolean;
}>();

const emit = defineEmits<{
    closed: [];
    saved: [idNumber: string];
    openMerge: [idNumber: string];
}>();

const { formatZimIdNumber } = useUtils();

const draftIdNumber = ref(
    props.row.suggestedIdNumber ?? props.row.storedIdNumber ?? props.row.idNumber ?? '',
);

const originalId = computed(() => (props.row.storedIdNumber ?? '').trim());
const isUnchanged = computed(() => draftIdNumber.value.trim() === originalId.value);
const isValid = computed(() => isValidZimbabweanIdNumber(draftIdNumber.value.trim()));
const isDuplicateMerge = computed(() => props.row.idRectificationStatus === 'duplicate_merge');

const onUseSuggested = (): void => {
    if (props.row.suggestedIdNumber) {
        draftIdNumber.value = props.row.suggestedIdNumber;
    }
};

const onSave = (): void => {
    if (props.saving || isUnchanged.value || !isValid.value) {
        return;
    }

    emit('saved', draftIdNumber.value.trim());
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 z-0 bg-black opacity-50" @click="emit('closed')" />
        <div class="relative z-10 m-2 w-full max-w-[640px] rounded-2xl bg-background shadow-lg">
            <div class="flex items-center justify-between px-6 pt-6">
                <h2 class="text-md font-semibold uppercase">
                    {{ trans('trans.maintenance_apprentice_import_fix_id_modal_title') }}
                </h2>
                <button
                    class="rounded-full p-2 hover:bg-muted"
                    type="button"
                    @click="emit('closed')"
                >
                    <component :is="icons[IconName.close]" :size="26" />
                </button>
            </div>

            <div class="space-y-4 px-6 py-4">
                <div class="space-y-1 text-sm">
                    <p class="font-medium">{{ row.studentName ?? '—' }}</p>
                    <p v-if="row.studentNumber" class="text-muted-foreground">
                        {{ trans_choice('trans.student_number', 1) }}: {{ row.studentNumber }}
                    </p>
                </div>

                <div class="space-y-2">
                    <p class="text-xs font-bold uppercase text-muted-foreground">
                        {{ trans('trans.maintenance_faulty_data_current_id') }}
                    </p>
                    <p class="font-mono text-sm text-destructive">{{ row.storedIdNumber ?? '—' }}</p>
                </div>

                <FaultyStudentIdCorrectionCell
                    v-model="draftIdNumber"
                    :suggested-id-number="row.suggestedIdNumber"
                    :input-id="`apprentice_faulty_id_number_correction_${row.studentId ?? row.rowNumber}`"
                    :disabled="saving"
                    :show-save-button="false"
                    @use-suggested="onUseSuggested"
                />

                <p
                    v-if="isDuplicateMerge && row.idConflict"
                    class="text-sm text-amber-700"
                >
                    {{ trans('trans.maintenance_faulty_data_id_conflict') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 border-t px-6 py-5">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.shade"
                    :size="ButtonSize.lg"
                    :disabled="saving"
                    @click="emit('closed')"
                >
                    {{ trans('trans.close') }}
                </BaseButton>
                <BaseButton
                    v-if="!isDuplicateMerge"
                    type="button"
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.lg"
                    :processing="saving"
                    :disabled="saving || isUnchanged || !isValid"
                    @click="onSave"
                >
                    {{ trans('trans.save') }}
                </BaseButton>
                <BaseButton
                    v-else-if="row.idConflict"
                    type="button"
                    :variant="ColorVariant.warning"
                    :size="ButtonSize.lg"
                    :disabled="saving || !isValid"
                    @click="emit('openMerge', draftIdNumber.trim())"
                >
                    {{ trans('trans.maintenance_faulty_data_compare_merge') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>

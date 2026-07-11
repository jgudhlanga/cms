<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import LabelValue from '@/components/core/util/LabelValue.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { warningDialog } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import type { StudentAccountMergePreview } from '@/types/faulty-student-ids';
import type { RadioGroupOption } from '@/types/forms';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

const props = defineProps<{
    preview: StudentAccountMergePreview;
    processing?: boolean;
}>();

const emit = defineEmits<{
    closed: [];
    merged: [];
}>();

const survivorStudentId = ref(String(props.preview.target.studentId));
const isSubmitting = ref(false);

const profiles = computed(() => [props.preview.source, props.preview.target]);

const survivorOptions = computed<RadioGroupOption[]>(() => [
    {
        inputId: 'apprentice_merge_survivor_source',
        label: props.preview.source.name ?? trans('trans.maintenance_faulty_data_merge_faulty_account'),
        value: String(props.preview.source.studentId),
    },
    {
        inputId: 'apprentice_merge_survivor_target',
        label: props.preview.target.name ?? trans('trans.maintenance_faulty_data_merge_existing_account'),
        value: String(props.preview.target.studentId),
    },
]);

const summaryFields = (summary: StudentAccountMergePreview['source']) => [
    { label: trans('trans.email_address'), value: summary.email ?? '---' },
    { label: trans('trans.phone_number'), value: summary.phoneNumber ?? '---' },
    { label: trans_choice('trans.student_number', 1), value: summary.studentNumber ?? '---' },
    { label: trans('trans.id_number'), value: summary.idNumber ?? '---', valueClasses: 'font-mono' },
];

const executeMerge = (): void => {
    warningDialog(
        () => {
            isSubmitting.value = true;

            void HttpService.post(route('maintenance.faulty-student-ids.merge.execute'), {
                source_student_id: props.preview.source.studentId,
                target_student_id: props.preview.target.studentId,
                survivor_student_id: Number(survivorStudentId.value),
                id_number: props.preview.proposedIdNumber,
            })
                .then(() => {
                    emit('merged');
                })
                .finally(() => {
                    isSubmitting.value = false;
                });

            return true;
        },
        trans('trans.maintenance_faulty_data_merge_confirm'),
        trans('trans.warning'),
        trans('trans.maintenance_faulty_data_merge_title'),
    );
};
</script>

<template>
    <div class="fixed inset-0 z-[60] flex items-center justify-center">
        <div class="absolute inset-0 z-0 bg-black opacity-50" @click="emit('closed')" />
        <div class="relative z-10 m-2 max-h-[90vh] w-full max-w-[900px] overflow-y-auto rounded-2xl bg-background shadow-lg">
            <div class="flex items-center justify-between px-6 pt-6">
                <h2 class="text-md font-semibold uppercase">
                    {{ trans('trans.maintenance_faulty_data_merge_title') }}
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
                <div class="rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
                    <span class="text-muted-foreground">{{ trans('trans.maintenance_faulty_data_merge_proposed_id') }}</span>
                    <span class="ml-2 font-mono font-semibold">{{ preview.proposedIdNumber }}</span>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div
                        v-for="profile in profiles"
                        :key="profile.studentId"
                        class="space-y-2 rounded-lg border border-border p-3"
                    >
                        <p class="text-sm font-medium">{{ profile.name ?? '—' }}</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <LabelValue
                                v-for="field in summaryFields(profile)"
                                :key="field.label"
                                :label="field.label"
                                :value="field.value"
                                :value-classes="field.valueClasses"
                            />
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <p class="text-sm font-medium">{{ trans('trans.maintenance_faulty_data_keep_account') }}</p>
                    <BaseRadioGroup
                        v-model="survivorStudentId"
                        name="apprentice_import_merge_survivor"
                        :options="survivorOptions"
                    />
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 border-t px-6 py-5">
                <BaseButton
                    type="button"
                    :variant="ColorVariant.shade"
                    :size="ButtonSize.lg"
                    :disabled="isSubmitting || processing"
                    @click="emit('closed')"
                >
                    {{ trans('trans.close') }}
                </BaseButton>
                <BaseButton
                    type="button"
                    :variant="ColorVariant.primary"
                    :size="ButtonSize.lg"
                    :processing="isSubmitting || processing"
                    :disabled="isSubmitting || processing"
                    @click="executeMerge"
                >
                    {{ trans('trans.maintenance_faulty_data_merge_title') }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>

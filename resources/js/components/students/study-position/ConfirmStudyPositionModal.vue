<script setup lang="ts">
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { TextFieldType } from '@/enums/inputs';
import { SizeVariant } from '@/enums/sizes';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useModalStore } from '@/store/core/useModalStore';
import type { StudyPositionItem, StudyPositionStatus } from '@/types/study-position';
import type { SelectOption } from '@/types/utils';
import { router, useForm } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps<{
    studentId: string | number;
    status: StudyPositionStatus;
}>();

const MODAL_KEY = APP_MODULE_KEYS.student_study_position_confirm;
const { closeModal, isOpen } = useModalStore();

const confirmable = computed<StudyPositionItem[]>(() => props.status.items.filter((item) => item.canConfirm));
const readOnly = computed<StudyPositionItem[]>(() => props.status.items.filter((item) => !item.canConfirm));

// Keyed by enrolment id.
const selections = reactive<Record<number, number | null>>({});

const form = useForm({
    positions: [] as Array<{ student_enrolment_id: number; programme_semester_id: number }>,
    reason: '',
});

const optionsFor = (item: StudyPositionItem): SelectOption[] => item.options.map((phase) => ({ value: phase.id, label: phase.label }));

const errorFor = (index: number): string | undefined =>
    (form.errors as Record<string, string | undefined>)[`positions.${index}.programme_semester_id`];

const resetSelections = (): void => {
    for (const item of confirmable.value) {
        selections[item.enrolmentId] = item.confirmation?.phase?.id ?? item.systemPhase?.id ?? null;
    }
};

watch(
    () => isOpen(MODAL_KEY),
    (opened) => {
        if (opened) {
            resetSelections();
            form.reason = '';
            form.clearErrors();
        }
    },
);

const onClose = (): void => {
    form.reset();
    form.clearErrors();
};

const save = (): void => {
    form.transform((data) => ({
        reason: data.reason.trim(),
        positions: confirmable.value
            .filter((item) => selections[item.enrolmentId] !== null && selections[item.enrolmentId] !== undefined)
            .map((item) => ({
                student_enrolment_id: item.enrolmentId,
                programme_semester_id: Number(selections[item.enrolmentId]),
            })),
    })).patch(route('students.study-position.update', props.studentId), {
        preserveScroll: true,
        onSuccess: (page) => {
            const errors = (page.props.errors ?? {}) as Record<string, string>;

            if (Object.keys(errors).length > 0) {
                return;
            }

            closeModal(MODAL_KEY);
            onClose();
            // Remount so the Programmes tab refetches the corrected phases.
            router.visit(window.location.href, { replace: true, preserveState: false, preserveScroll: true });
        },
    });
};
</script>

<template>
    <BaseModal
        :name="MODAL_KEY"
        :title="$t('students.study_position_modal_title')"
        :size="SizeVariant.sm"
        action-btn-text="trans.save"
        cancel-btn-text="trans.cancel"
        :show-action-button="confirmable.length > 0"
        :on-form-action="() => save()"
        :on-close-modal="onClose"
        :form="form"
    >
        <template #body>
            <div class="grid grid-cols-1 gap-4">
                <p class="text-muted-foreground text-xs">
                    {{ $t('students.study_position_modal_help', { period: status.periodLabel ?? '' }) }}
                </p>

                <section v-for="(item, index) in confirmable" :key="item.enrolmentId" class="border-border flex flex-col gap-2 rounded-lg border p-3">
                    <BaseSelect
                        v-model="selections[item.enrolmentId]"
                        :label="$t('students.study_position_modal_field', { programme: item.programme.label })"
                        :placeholder="$t('students.study_position_modal_field_placeholder')"
                        :options="optionsFor(item)"
                        :is-clearable="false"
                        :error="errorFor(index)"
                        is-required
                        @update:model-value="clearFormErrors(form, `positions.${index}.programme_semester_id`)"
                    />
                    <p class="text-muted-foreground text-xs">
                        {{
                            item.systemPhase
                                ? $t('students.study_position_modal_on_record', { phase: item.systemPhase.label })
                                : $t('students.study_position_records_missing')
                        }}
                    </p>
                    <p v-if="item.confirmation" class="text-muted-foreground text-xs">
                        {{
                            $t('students.study_position_modal_student_answer', {
                                answer: item.confirmation.phase?.label ?? item.confirmation.answerLabel,
                            })
                        }}
                        · {{ item.confirmation.sourceLabel }}
                    </p>
                    <p
                        v-if="item.confirmation?.syncNote"
                        class="rounded border border-amber-300 bg-amber-50 px-2 py-1 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
                    >
                        {{ item.confirmation.syncNote }}
                    </p>
                </section>

                <div v-if="readOnly.length > 0" class="border-border text-muted-foreground rounded-lg border border-dashed p-3 text-xs">
                    <p class="font-medium">{{ $t('students.study_position_modal_no_access') }}</p>
                    <ul class="mt-1 list-disc ps-4">
                        <li v-for="item in readOnly" :key="item.enrolmentId">{{ item.programme.label }}</li>
                    </ul>
                </div>

                <BaseInput
                    v-if="confirmable.length > 0"
                    input-id="student_study_position_reason"
                    v-model="form.reason"
                    :type="TextFieldType.textarea"
                    rows="3"
                    :label="$t('students.study_position_reason_label')"
                    :placeholder="$t('students.study_position_reason_placeholder')"
                    :is-required="true"
                    :error="form.errors.reason"
                    @input="clearFormErrors(form, 'reason')"
                />
            </div>
        </template>
    </BaseModal>
</template>

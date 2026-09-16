<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { useStudyPositionPrompt } from '@/composables/students/useStudyPositionPrompt';
import { SizeVariant } from '@/enums/sizes';
import { Info } from 'lucide-vue-next';
import { computed } from 'vue';

const {
    MODAL_KEY,
    required,
    status,
    isLoading,
    loadError,
    answers,
    form,
    answerableItems,
    answeredItems,
    periodLabel,
    optionsFor,
    errorFor,
    onClose,
    submit,
} = useStudyPositionPrompt();

// Forced only while there is something the student can actually answer; a failed load must never trap them.
const dismissible = computed(() => !required.value || loadError.value || (status.value !== null && answerableItems.value.length === 0));
const canSubmit = computed(() => !isLoading.value && !loadError.value && answerableItems.value.length > 0);
</script>

<template>
    <BaseModal
        :name="MODAL_KEY"
        :title="$t('students.study_position_prompt_title')"
        :size="SizeVariant.sm"
        action-btn-text="students.study_position_submit"
        cancel-btn-text="trans.close"
        :dismissible="dismissible"
        :show-action-button="canSubmit"
        :on-form-action="submit"
        :on-close-modal="onClose"
        :form="form"
        stack-footer-on-mobile
    >
        <template #body>
            <div class="grid grid-cols-1 gap-4">
                <p class="text-foreground text-sm">
                    {{ $t('students.study_position_prompt_intro', { period: periodLabel }) }}
                </p>

                <div
                    class="flex items-start gap-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"
                >
                    <Info class="mt-0.5 size-4 shrink-0" />
                    <p>{{ $t('students.study_position_prompt_billing_note') }}</p>
                </div>

                <DataLoadingSpinner v-if="isLoading" :message="$t('students.study_position_loading')" />

                <p
                    v-else-if="loadError"
                    class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100"
                    role="alert"
                >
                    {{ $t('students.study_position_load_failed') }}
                </p>

                <template v-else>
                    <section
                        v-for="(item, index) in answerableItems"
                        :key="item.enrolmentId"
                        class="border-border flex flex-col gap-3 rounded-lg border p-3"
                    >
                        <div class="min-w-0">
                            <p class="text-sm font-semibold break-words">{{ item.programme.label }}</p>
                            <p class="text-muted-foreground text-xs">
                                {{ [item.programme.department, item.programme.modeOfStudy].filter(Boolean).join(' · ') }}
                            </p>
                            <p class="text-muted-foreground mt-1 text-xs">
                                {{
                                    item.systemPhase
                                        ? $t('students.study_position_records_show', { phase: item.systemPhase.label })
                                        : $t('students.study_position_records_missing')
                                }}
                            </p>
                        </div>

                        <BaseRadioGroup
                            v-model="answers[item.enrolmentId]"
                            :label="$t('students.study_position_field_label')"
                            :options="optionsFor(item)"
                            :error="errorFor(index)"
                            :disabled="form.processing"
                            is-required
                            mobile-stack
                            class="gap-2"
                        />
                        <InputError :message="errorFor(index)" />
                    </section>

                    <ul v-if="answeredItems.length > 0" class="text-muted-foreground flex flex-col gap-1 text-xs">
                        <li v-for="item in answeredItems" :key="item.enrolmentId" class="break-words">
                            <span class="text-foreground font-medium">{{ item.programme.label }}</span>
                            —
                            {{
                                $t('students.study_position_already_answered', {
                                    answer: item.confirmation?.phase?.label ?? item.confirmation?.answerLabel ?? '',
                                })
                            }}
                        </li>
                    </ul>

                    <p class="text-muted-foreground text-xs">{{ $t('students.study_position_follow_up_note') }}</p>
                    <InputError :message="form.errors.answers" />
                </template>
            </div>
        </template>
    </BaseModal>
</template>

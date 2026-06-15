<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import IdNumber from '@/components/core/form/text/IdNumber.vue';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { EnrollmentLookupResult, ReturningLookupType } from '@/composables/students/useEnrollmentRegistration';

type EnrollmentPath = 'zimbabwean' | 'returning' | 'international';

const returningLookupType = defineModel<ReturningLookupType>('returningLookupType', { required: true });

defineProps<{
    activePath: EnrollmentPath;
    returningLookupValue: string;
    idNumber: string;
    passportNumber: string;
    duplicateResult: EnrollmentLookupResult | null;
    existingRecordBlocked: boolean;
    returningRecordFound: boolean;
    lookupError: string | null;
    isChecking: boolean;
    formErrors: Record<string, string | undefined>;
}>();

const emit = defineEmits<{
    (e: 'update:returningLookupValue', value: string): void;
    (e: 'update:idNumber', value: string): void;
    (e: 'update:passportNumber', value: string): void;
    (e: 'clear-error', field: string): void;
    (e: 'continue'): void;
    (e: 'switch-path', path: EnrollmentPath): void;
    (e: 'continue-login'): void;
}>();
</script>

<template>
    <div class="flex flex-col space-y-4">
        <h2 class="text-sm font-semibold uppercase text-foreground">
            {{ $t('trans.enrollment_step_identity') }}
        </h2>

        <template v-if="activePath === 'returning'">
            <div class="grid grid-cols-2 gap-2">
                <button
                    type="button"
                    class="rounded-lg border px-2 py-2 text-xs font-medium uppercase"
                    :class="
                        returningLookupType === 'id_number'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-border text-muted-foreground'
                    "
                    @click="returningLookupType = 'id_number'"
                >
                    {{ $t('trans.enrollment_lookup_by_id') }}
                </button>
                <button
                    type="button"
                    class="rounded-lg border px-2 py-2 text-xs font-medium uppercase"
                    :class="
                        returningLookupType === 'student_number'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-border text-muted-foreground'
                    "
                    @click="returningLookupType = 'student_number'"
                >
                    {{ $t('trans.enrollment_lookup_by_student_number') }}
                </button>
            </div>

            <IdNumber
                v-if="returningLookupType === 'id_number'"
                :model-value="returningLookupValue"
                :placeholder="$t('trans.enrollment_enter_national_id')"
                :vertical-layout="false"
                :label-uppercase="true"
                :is-required="true"
                @update:model-value="emit('update:returningLookupValue', $event)"
            />
            <BaseInput
                v-else
                input-id="returning_student_number"
                :model-value="returningLookupValue"
                :placeholder="$t('trans.enrollment_enter_student_number')"
                :vertical-layout="false"
                :label-uppercase="true"
                :is-required="true"
                @update:model-value="emit('update:returningLookupValue', $event)"
            />
        </template>

        <template v-else-if="activePath === 'international'">
            <BaseInput
                input-id="passport_number"
                :model-value="passportNumber"
                :placeholder="$t('trans.enrollment_enter_passport')"
                :vertical-layout="false"
                :label-uppercase="true"
                :is-required="true"
                :error="formErrors.passport_number"
                @update:model-value="emit('update:passportNumber', $event)"
                @input="emit('clear-error', 'passport_number')"
            />
        </template>

        <template v-else>
            <IdNumber
                :model-value="idNumber"
                :placeholder="$t('trans.enrollment_enter_national_id')"
                :vertical-layout="false"
                :label-uppercase="true"
                :is-required="true"
                :error="formErrors.id_number"
                @update:model-value="emit('update:idNumber', $event)"
                @input="emit('clear-error', 'id_number')"
            />
        </template>

        <BaseAlert
            v-if="existingRecordBlocked"
            :type="TypeVariant.danger"
            :title="$t('trans.enrollment_existing_record_detected')"
            :description="$t('trans.enrollment_existing_record_message')"
        />
        <div
            v-if="existingRecordBlocked && duplicateResult?.maskedName"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900 dark:border-red-900/50 dark:bg-red-950/50 dark:text-red-100"
        >
            <p>
                <span class="font-medium">{{ $t('trans.enrollment_masked_name') }}:</span>
                {{ duplicateResult.maskedName }}
            </p>
        </div>

        <BaseAlert
            v-if="returningRecordFound"
            :type="TypeVariant.success"
            :title="$t('trans.enrollment_record_found')"
            :description="$t('trans.enrollment_record_found_message')"
        />
        <div
            v-if="returningRecordFound"
            class="space-y-2 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-950 dark:border-green-900/50 dark:bg-green-950/45 dark:text-green-100"
        >
            <p v-if="duplicateResult?.maskedName">
                <span class="font-medium">{{ $t('trans.enrollment_masked_name') }}:</span>
                {{ duplicateResult.maskedName }}
            </p>
            <p v-if="duplicateResult?.maskedEmail">
                <span class="font-medium">{{ $t('trans.enrollment_masked_email') }}:</span>
                {{ duplicateResult.maskedEmail }}
            </p>
        </div>

        <BaseAlert
            v-if="activePath === 'returning' && duplicateResult && !duplicateResult.found"
            :type="TypeVariant.warning"
            :title="$t('trans.enrollment_record_not_found')"
            :description="$t('trans.enrollment_record_not_found_message')"
        />

        <p v-if="lookupError" class="text-sm text-red-600 dark:text-red-400">{{ lookupError }}</p>

        <div class="flex flex-col gap-2 pt-2">
            <BaseButton v-if="!returningRecordFound" type="button" class="w-full" :processing="isChecking" @click="emit('continue')">
                {{ $t('trans.enrollment_continue') }}
            </BaseButton>

            <BaseButton
                v-if="existingRecordBlocked"
                type="button"
                class="w-full"
                :variant="ColorVariant.primary_outline"
                @click="emit('switch-path', 'returning')"
            >
                {{ $t('trans.enrollment_switch_to_returning') }}
            </BaseButton>

            <BaseButton v-if="returningRecordFound" type="button" class="w-full" @click="emit('continue-login')">
                {{ $t('trans.enrollment_continue_to_login') }}
            </BaseButton>

            <BaseButton
                v-if="activePath === 'returning' && duplicateResult && !duplicateResult.found"
                type="button"
                class="w-full"
                :variant="ColorVariant.primary_outline"
                @click="emit('switch-path', 'zimbabwean')"
            >
                {{ $t('trans.enrollment_switch_to_new') }}
            </BaseButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import InputError from '@/components/core/form/InputError.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import type { HostelApplicationEligibilityRule } from '@/types/hms';
import type { InertiaForm } from '@inertiajs/vue3';

interface FormShape {
    nextOfKinName: string;
    nextOfKinContact: string;
    checkIn: string;
    checkOut: string;
}

interface Props {
    form: InertiaForm<FormShape>;
    eligibility?: HostelApplicationEligibilityRule[] | null;
    semesterLabel?: string | null;
    checkIn?: string | null;
    checkOut?: string | null;
    canSubmit: boolean;
    isSaving: boolean;
    saveValidationError?: string | null;
}

defineProps<Props>();

const emit = defineEmits<{
    submit: [];
}>();
</script>

<template>
    <div class="flex flex-col gap-4">
        <BaseAlert
            v-if="semesterLabel"
            :description="$t('students.accommodation_semester_notice', { label: semesterLabel })"
            :type="TypeVariant.info"
        />

        <div
            v-if="eligibility?.length"
            class="rounded-lg border border-border bg-muted/20 p-3"
        >
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                {{ $t('students.accommodation_eligibility') }}
            </p>
            <ul class="flex flex-col gap-1.5">
                <li
                    v-for="rule in eligibility"
                    :key="rule.key"
                    class="text-sm"
                    :class="rule.passed ? 'text-emerald-600 dark:text-emerald-400' : 'text-destructive'"
                >
                    {{ rule.message }}
                </li>
            </ul>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <BaseInput
                    v-model="form.nextOfKinName"
                    :label="$t('hms.next_of_kin_name')"
                    required
                />
                <InputError :message="form.errors.nextOfKinName" />
            </div>
            <div>
                <BaseInput
                    v-model="form.nextOfKinContact"
                    :label="$t('hms.next_of_kin_contact')"
                    required
                />
                <InputError :message="form.errors.nextOfKinContact" />
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <BaseInput
                :model-value="checkIn ?? ''"
                :label="$t('hms.check_in')"
                disabled
            />
            <BaseInput
                :model-value="checkOut ?? ''"
                :label="$t('hms.check_out')"
                disabled
            />
        </div>

        <BaseAlert
            v-if="saveValidationError"
            :description="saveValidationError"
            :type="TypeVariant.danger"
        />

        <BaseButton
            :color="ColorVariant.primary"
            :size="ButtonSize.md"
            :disabled="!canSubmit || isSaving"
            :loading="isSaving"
            @click="emit('submit')"
        >
            {{ $t('students.accommodation_submit_application') }}
        </BaseButton>
    </div>
</template>

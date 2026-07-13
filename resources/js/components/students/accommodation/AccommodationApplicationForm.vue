<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import HostelEligibilityStatus from '@/components/hms/HostelEligibilityStatus.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import InputError from '@/components/core/form/InputError.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
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
    showCheckOut?: boolean;
    canSubmit: boolean;
    isSaving: boolean;
    saveValidationError?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    showCheckOut: false,
});

const emit = defineEmits<{
    submit: [];
}>();
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            v-if="semesterLabel || eligibility?.length"
            class="grid grid-cols-1 gap-x-4 gap-y-1.5 md:grid-cols-3"
        >
            <p
                v-if="semesterLabel"
                class="text-sm leading-snug text-sky-600 dark:text-sky-400"
            >
                {{ $t('students.accommodation_semester_notice', { label: semesterLabel }) }}
            </p>

            <HostelEligibilityStatus
                v-if="eligibility?.length"
                :rules="eligibility"
                :show-heading="false"
                show-advisory-notice
                grid
            />
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

        <div v-if="checkIn || showCheckOut" class="grid gap-3 sm:grid-cols-2">
            <BaseInput
                v-if="checkIn"
                :model-value="checkIn ?? ''"
                :label="$t('hms.check_in')"
                disabled
            />
            <BaseInput
                v-if="showCheckOut"
                :model-value="checkOut ?? ''"
                :label="$t('hms.check_out')"
                disabled
            />
        </div>

        <p
            v-if="saveValidationError"
            class="text-sm text-destructive"
        >
            {{ saveValidationError }}
        </p>

        <div class="flex">
            <BaseButton
                :classes="'w-full self-start sm:w-auto'"
                :variant="ColorVariant.primary"
                :size="ButtonSize.md"
                :processing="isSaving"
                :disabled="!canSubmit || isSaving"
                @click="emit('submit')"
            >
                {{ $t('students.accommodation_submit_application') }}
            </BaseButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { BaseButton } from '@/components/core/button';
import PageContainer from '@/components/core/page/PageContainer.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { AuthObject } from '@/types/data-pagination';
import { IntakePeriod } from '@/types/institution';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    auth: AuthObject;
    errors: object;
    openIntakes: IntakePeriod[] | { data: IntakePeriod[] };
}

const props = defineProps<Props>();

const intakeList = computed(() => {
    if (Array.isArray(props.openIntakes)) {
        return props.openIntakes;
    }

    return Array.isArray(props.openIntakes?.data) ? props.openIntakes.data : [];
});

const selectedIntakeId = ref<number | null>(
    intakeList.value.length === 1 ? Number(intakeList.value[0].id) : null,
);

const continueForm = useForm({
    intake_period_id: null as number | null,
    student_number: '',
    acknowledged: false as boolean,
});

const submitContinue = () => {
    continueForm.intake_period_id = selectedIntakeId.value;
    continueForm.post(route('portal.returning-student.continue'));
};
</script>

<template>
    <Head :title="$t('trans.returning_student_continue_in_class_title')" />
    <PageContainer>
        <div class="mx-auto max-w-2xl space-y-6 py-6">
            <div class="text-center">
                <h1 class="text-2xl font-semibold text-foreground">
                    {{ $t('trans.returning_student_continue_in_class_title') }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ $t('trans.returning_student_continue_in_class_description') }}
                </p>
            </div>

            <BaseAlert
                :type="TypeVariant.info"
                :description="$t('trans.returning_student_path_continue')"
            />

            <div v-if="intakeList.length > 1" class="rounded-xl border border-border bg-card p-4">
                <IntakePeriodComboSelect v-model="selectedIntakeId" :data="intakeList" :is-required="true" />
            </div>

            <form class="space-y-4 rounded-xl border border-border bg-card p-4" @submit.prevent="submitContinue">
                <label class="block text-sm font-medium text-foreground">
                    {{ $t('trans.returning_student_student_number_label') }}
                    <input
                        v-model="continueForm.student_number"
                        type="text"
                        class="mt-1 w-full rounded-lg border border-border bg-background px-3 py-2"
                    />
                </label>
                <label class="flex items-start gap-2 text-sm">
                    <input v-model="continueForm.acknowledged" type="checkbox" class="mt-1" />
                    <span>{{ $t('trans.returning_student_acknowledge_label') }}</span>
                </label>
                <BaseButton
                    type="submit"
                    :variant="ColorVariant.primary"
                    :disabled="!continueForm.acknowledged || !continueForm.student_number || !selectedIntakeId"
                    :title="$t('trans.returning_student_continue_submit')"
                />
            </form>
        </div>
    </PageContainer>
</template>

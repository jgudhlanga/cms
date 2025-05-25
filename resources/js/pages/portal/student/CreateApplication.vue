<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useStudentPortal } from '@/composables/portal/useStudentPortal';
import { useUtils } from '@/composables/core/useUtils';
import { AuthObject } from '@/types/data-pagination';
import { BreadcrumbItemInterface } from '@/types/ui';
import { User } from '@/types/users';
import { Head } from '@inertiajs/vue3';
import BaseStepperButtons from '@/components/core/stepper/BaseStepperButtons.vue';
import BaseStepperItem from '@/components/core/stepper/BaseStepperItem.vue';
import { Stepper } from '@/components/ui/stepper';
import { ref } from 'vue';
import { Step } from '@/types/forms';

interface Props {
    user: User;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const {  } = useStudentPortal();
const { navigateTo } = useUtils();
const { user } = props;
const stepIndex = ref(1);
const maxStep = 5;
const metaValid = ref(false);
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'finish_your_application' }];
const steps: Step[] = [
    { step: 1, title: 'Personal ', description: 'provide personal details' },
    { step: 2, title: 'Contact details', description: 'provide contact details' },
    { step: 3, title: 'Next of kin', description: 'provide next of kin details' },
    { step: 4, title: 'Programs', description: 'Select programs' },
    { step: 5, title: 'Confirmation', description: 'Confirm your application' },
];

const goNext = (next: () => void) => {
    console.log(next);
};
</script>
<template>
    <Head :title="$t('trans.create_new_application')" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <form @submit.prevent=" () => {}">
            <Stepper
                orientation="vertical"
                v-slot="{ isPrevDisabled, nextStep, prevStep }" v-model="stepIndex"
                class="flex w-full flex-col">
                <BaseStepperItem :steps="steps" :meta-valid="metaValid" />
                <!-- CONTENT -->
                <div class="mt-4 flex flex-col gap-4">
                    <template v-if="stepIndex === 1">
                       <p>Personal Details</p>
                    </template>
                    <template v-if="stepIndex === 2">
                        <p>Contacts</p>
                    </template>
                    <template v-if="stepIndex === 3">
                        <p>Programs</p>
                    </template>
                    <template v-if="stepIndex === 4">
                        <p>Next of kin</p>
                    </template>
                    <template v-if="stepIndex === maxStep">
                        <p>Confirm</p>
                    </template>
                </div>
                <!-- BUTTONS -->
                <BaseStepperButtons
                    :step-index="stepIndex"
                    :prev-step-action="() => prevStep()"
                    :next-step-action="() => goNext(() => nextStep())"
                    :previous-disabled="isPrevDisabled"
                    :max-step="maxStep"
                />
            </Stepper>
        </form>
    </PageContainer>
</template>

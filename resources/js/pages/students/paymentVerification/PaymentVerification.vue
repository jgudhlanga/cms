<script lang="ts" setup>
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseCard from '@/components/core/card/BaseCard.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import { useUtils } from '@/composables/core/useUtils';
import { TypeVariant } from '@/enums/type-variants';
import CheckStudentStatus from '@/pages/students/paymentVerification/cash/CheckStudentStatus.vue';
import CheckPaymentStatus from '@/pages/students/paymentVerification/online/CheckPaymentStatus.vue';
import PaymentStatusModal from '@/pages/students/paymentVerification/online/PaymentStatusModal.vue';
import { AuthObject } from '@/types/data-pagination';
import { FeeStructure } from '@/types/institution';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    registrationFee: FeeStructure;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();

const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    { title: 'Payment Verification' },
];
const { formatCurrency } = useUtils();
const registrationFeeAmount = props.registrationFee?.attributes?.localFcaAmount ?? '20.00';

const paymentDescriptions = {
    online: 'The applicant paid the application fee through the Smile & Pay payment gateway but did not finish the application process. It is assumed they already have a user account.',
    cash: 'The applicant paid the application fee at the bank and now needs to be enrolled manually. You have to upload the payment receipt / slip. We will verify with the Accounts Department',
};

const paymentOptions = [
    { type: 'online', title: 'Applicant Paid Online', description: paymentDescriptions.online },
    { type: 'cash', title: 'Applicant Paid Cash at the Bank', description: paymentDescriptions.cash },
];
</script>

<template>
    <Head :title="$tChoice('enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <BaseAlert
            :description="`Note that applicants are required to pay an application fee amount of USD${formatCurrency(registrationFeeAmount)}`"
            :type="TypeVariant.danger"
        />
        <div class="my-10 flex flex-col space-y-5">
            <BaseCard v-for="option in paymentOptions" :key="option.type" :title="option.title" :description="option.description">
                <CheckPaymentStatus v-if="option.type === 'online'" />
                <CheckStudentStatus v-if="option.type === 'cash'" />
            </BaseCard>
        </div>
        <PaymentStatusModal />
    </PageContainer>
</template>

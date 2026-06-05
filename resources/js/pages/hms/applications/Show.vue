<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useHms } from '@/composables/hms/useHms';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { openModal } from '@/lib/alerts';
import { IconName } from '@/enums/icons';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import HostelEligibilityStatus from '@/components/hms/HostelEligibilityStatus.vue';
import ApplicationSidebar from '@/pages/hms/applications/partials/ApplicationSidebar.vue';
import PaymentVerificationCard from '@/pages/hms/applications/partials/PaymentVerificationCard.vue';
import DeclineApplication from '@/pages/hms/components/forms/DeclineApplication.vue';
import { useHmsStore } from '@/store/hms/useHmsStore';
import type { HostelApplication, HostelApplicationEligibilityRule, HmsSettings } from '@/types/hms';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';
import { ButtonSize } from '@/enums/buttons';

interface Props {
    applicationId: number | string;
}

const props = defineProps<Props>();

const { formatDate } = useUtils();
const { fetchApplication, fetchHmsSettings, updateApplicationStatus, isLoading } = useHms();
const hmsStore = useHmsStore();
const { open: openConfirm } = useCustomConfirmDialog();

const application = ref<HostelApplication | null>(null);
const hmsSettings = ref<HmsSettings | null>(null);

const attrs = computed(() => application.value?.attributes);

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transChoiceKey: 'hms.title', href: route('hostels.index') },
    { transChoiceKey: 'trans.application', href: route('hostels.index') },
    { title: attrs.value?.displayName ?? trans('hms.view_application') },
]);

const isStudentApplication = computed(() => attrs.value?.applicationType === 'student');

const canReviewPending = computed(
    () => attrs.value?.status === 'pending' && hasAbility('update:hostel-applications'),
);

const allowsDirectAllocation = computed(() => {
    const settings = hmsSettings.value?.attributes;

    if (!settings) {
        return false;
    }

    return (
        !settings.requireFullTimeStudy &&
        !settings.requireTuitionPaid &&
        !settings.requireAccommodationPaid &&
        !settings.requireAddressOutsideCampus
    );
});

const canReviewAwaitingPayment = computed(
    () => attrs.value?.status === 'awaiting-payment' && hasAbility('update:hostel-applications'),
);

const canAllocateRoom = computed(() => {
    if (!hasAbility('update:hostel-applications') || !isStudentApplication.value) {
        return false;
    }

    if (attrs.value?.status === 'awaiting-payment') {
        return true;
    }

    return attrs.value?.status === 'pending' && allowsDirectAllocation.value;
});

const showPaymentStepOnPending = computed(
    () => canReviewPending.value && allowsDirectAllocation.value,
);

const loadApplication = async () => {
    application.value = (await fetchApplication(props.applicationId)) ?? null;
};

onMounted(async () => {
    hmsStore.activeTab = 'applications';
    hmsSettings.value = (await fetchHmsSettings()) ?? null;
    await loadApplication();
});

const openDecline = () => {
    if (!application.value) {
        return;
    }

    openModal({ name: APP_MODULE_KEYS.hostel_application_decline, edit: application.value });
};

const requestPayment = async () => {
    if (!application.value) {
        return;
    }

    const confirmed = await openConfirm({
        title: trans('hms.approve_application'),
        message: trans('hms.approve_payment_helper'),
        note: '',
        confirmText: trans('hms.approve_application'),
    });

    if (!confirmed) {
        return;
    }

    const ok = await updateApplicationStatus(application.value, 'awaiting-payment');

    if (ok) {
        await loadApplication();
    }
};

const onApprovedAndAllocated = async () => {
    await loadApplication();
};

const studentProfileUrl = computed(() => {
    const studentId = attrs.value?.studentId;

    if (!studentId || !hasAbility('view:students')) {
        return null;
    }

    return route('students.show', String(studentId));
});

const eligibilityRules = computed((): HostelApplicationEligibilityRule[] => attrs.value?.eligibilityResults ?? []);

const showAccommodationEligibility = computed(
    () => attrs.value?.status === 'awaiting-payment',
);
</script>

<template>
    <Head :title="attrs?.displayName ?? $t('hms.view_application')" />

    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('hostels.index')">
        <template #backNavigationLeading>
            <BaseButton
                v-if="studentProfileUrl"
                :size="ButtonSize.sm"
                :variant="ColorVariant.primary_outline"
                classes="rounded-full"
                :title="`${$tChoice('trans.student', 1)} ${$tChoice('trans.profile', 1)}`"
                type="button"
                @click="router.visit(studentProfileUrl)"
            >
                <BaseIcon :name="IconName.user" :color="ColorVariant.shade" />
            </BaseButton>
        </template>

        <div v-if="isLoading && !application" class="py-8 text-center text-muted-foreground">
            {{ $t('trans.loading') }}…
        </div>

        <div v-else-if="application && attrs" class="flex justify-between space-x-3">
            <div class="flex w-3/4 flex-col space-y-3">
                <BaseAlert
                    v-if="attrs.status === 'pending' && isStudentApplication && canReviewPending && showPaymentStepOnPending"
                    :type="TypeVariant.info"
                    :description="$t('hms.approve_direct_allocation_helper')"
                />
                <BaseAlert
                    v-else-if="attrs.status === 'pending' && isStudentApplication && canReviewPending"
                    :type="TypeVariant.info"
                    :description="$t('hms.approve_payment_helper')"
                />
                <BaseAlert
                    v-if="attrs.status === 'awaiting-payment'"
                    :type="TypeVariant.warning"
                    :description="$t('hms.awaiting_payment_notice')"
                />
                <BaseAlert
                    v-if="attrs.status === 'declined' && attrs.declineReason"
                    :type="TypeVariant.danger"
                    :title="$t('hms.decline_reason')"
                    :description="attrs.declineReason"
                />

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <BaseInput
                        input-id="application-display-name"
                        :model-value="attrs.displayName ?? ''"
                        :label="$tChoice('trans.name', 1)"
                        disabled
                    />
                    <BaseInput
                        v-if="isStudentApplication"
                        input-id="application-student-number"
                        :model-value="attrs.studentNumber ?? ''"
                        :label="$tChoice('trans.student_number', 1)"
                        disabled
                    />
                    <BaseInput
                        input-id="application-gender"
                        :model-value="attrs.gender ?? ''"
                        :label="$tChoice('trans.gender', 1)"
                        disabled
                    />
                    <BaseInput
                        v-if="isStudentApplication"
                        input-id="application-department"
                        :model-value="attrs.departmentName ?? ''"
                        :label="$tChoice('trans.department', 1)"
                        disabled
                    />
                    <BaseInput
                        v-if="isStudentApplication"
                        input-id="application-calendar-year"
                        :model-value="attrs.calendarYear ?? ''"
                        :label="$tChoice('academic_calendar.calendar_year', 1)"
                        disabled
                    />
                    <BaseInput
                        v-if="isStudentApplication"
                        input-id="application-course"
                        :model-value="attrs.course ?? ''"
                        :label="$tChoice('trans.course', 1)"
                        disabled
                    />
                    <BaseInput
                        v-if="isStudentApplication"
                        input-id="application-level"
                        :model-value="attrs.level ?? ''"
                        :label="$tChoice('trans.level', 1)"
                        disabled
                    />
                    <BaseInput
                        input-id="application-phone"
                        :model-value="attrs.phoneNumber ?? ''"
                        :label="$tChoice('trans.phone', 1)"
                        disabled
                    />
                    <BaseInput
                        input-id="application-email"
                        :model-value="attrs.emailAddress ?? ''"
                        :label="$t('trans.email')"
                        disabled
                    />
                    <BaseInput
                        v-if="isStudentApplication"
                        input-id="application-physical-address"
                        class="md:col-span-2"
                        :model-value="attrs.physicalAddress ?? ''"
                        :label="$t('hms.physical_address')"
                        disabled
                    />
                    <BaseInput
                        input-id="application-next-of-kin-name"
                        :model-value="attrs.nextOfKinName ?? ''"
                        :label="$t('hms.next_of_kin_name')"
                        disabled
                    />
                    <BaseInput
                        input-id="application-next-of-kin-contact"
                        :model-value="attrs.nextOfKinContact ?? ''"
                        :label="$t('hms.next_of_kin_contact')"
                        disabled
                    />
                    <BaseInput
                        input-id="application-check-in"
                        :model-value="attrs.checkIn ? formatDate(attrs.checkIn, 'L') : ''"
                        :label="$t('hms.check_in')"
                        disabled
                    />
                    <BaseInput
                        input-id="application-check-out"
                        :model-value="attrs.checkOut ? formatDate(attrs.checkOut, 'L') : ''"
                        :label="$t('hms.check_out')"
                        disabled
                    />
                </div>

                <div v-if="isStudentApplication && eligibilityRules.length" class="space-y-1.5">
                    <HeadingSmall :title="$t('hms.eligibility_rules_heading')" />
                    <div class="grid grid-cols-1 gap-x-4 gap-y-1.5 md:grid-cols-3">
                        <HostelEligibilityStatus
                            :rules="eligibilityRules"
                            :show-heading="false"
                            :show-accommodation="showAccommodationEligibility"
                            grid
                        />
                    </div>
                </div>

                <PaymentVerificationCard
                    v-if="canAllocateRoom"
                    :application="application"
                    @approved="onApprovedAndAllocated"
                    @decline="openDecline"
                />

                <div v-if="canReviewPending && !showPaymentStepOnPending" class="flex flex-wrap gap-2">
                    <BaseButton
                        type="button"
                        :variant="ColorVariant.danger"
                        :title="$t('hms.decline_application')"
                        @click="openDecline"
                    />
                    <BaseButton
                        v-if="isStudentApplication"
                        type="button"
                        :title="$t('hms.approve_application')"
                        @click="requestPayment"
                    />
                </div>
            </div>

            <div class="flex w-1/4 flex-col space-y-3">
                <ApplicationSidebar :current-application-id="applicationId" />
            </div>
        </div>

        <DeclineApplication @declined="loadApplication" />
    </PageContainer>
</template>

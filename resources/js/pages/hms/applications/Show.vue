<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import HeadingSmall from '@/components/core/util/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useHms } from '@/composables/hms/useHms';
import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { openModal } from '@/lib/alerts';
import { IconName } from '@/enums/icons';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import DeclineApplication from '@/pages/hms/components/forms/DeclineApplication.vue';
import { useHmsStore } from '@/store/hms/useHmsStore';
import type { HostelApplication, HostelApplicationEligibilityRule } from '@/types/hms';
import type { BreadcrumbItemInterface } from '@/types/ui';
import { Head, router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref } from 'vue';

interface Props {
    applicationId: number | string;
}

const props = defineProps<Props>();

const { formatDate } = useUtils();
const { fetchApplication, updateApplicationStatus, isLoading } = useHms();
const { tag } = useDataTables();
const hmsStore = useHmsStore();
const { open: openConfirm } = useCustomConfirmDialog();

const application = ref<HostelApplication | null>(null);

const attrs = computed(() => application.value?.attributes);

const breadcrumbs = computed<BreadcrumbItemInterface[]>(() => [
    { transChoiceKey: 'hms.title', href: route('hostels.index') },
    { title: attrs.value?.displayName ?? trans('hms.view_application') },
]);

const isStudentApplication = computed(() => attrs.value?.applicationType === 'student');

const canReview = computed(
    () => attrs.value?.status === 'pending' && hasAbility('update:hostel-applications'),
);

const statusTagVariant = computed(() => {
    switch (attrs.value?.status) {
        case 'approved':
            return ColorVariant.success;
        case 'declined':
            return ColorVariant.danger;
        case 'awaiting-payment':
            return ColorVariant.warning;
        default:
            return ColorVariant.primary;
    }
});

const loadApplication = async () => {
    application.value = (await fetchApplication(props.applicationId)) ?? null;
};

onMounted(async () => {
    hmsStore.activeTab = 'applications';
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

const studentProfileUrl = computed(() => {
    const studentId = attrs.value?.studentId;

    if (!studentId || !hasAbility('view:students')) {
        return null;
    }

    return route('students.show', String(studentId));
});

const eligibilityRules = computed((): HostelApplicationEligibilityRule[] => attrs.value?.eligibilityResults ?? []);

const statusTag = computed(() =>
    tag(attrs.value?.statusLabel ?? attrs.value?.status ?? '', '', statusTagVariant.value),
);
</script>

<template>
    <Head :title="attrs?.displayName ?? $t('hms.view_application')" />

    <PageContainer :breadcrumbs="breadcrumbs" :back-url="route('hostels.index')">
        <div v-if="isLoading && !application" class="py-8 text-center text-muted-foreground">
            {{ $t('trans.loading') }}…
        </div>

        <template v-else-if="application && attrs">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="space-y-2">
                    <HeadingSmall :title="attrs.displayName ?? '—'" />
                    <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <span v-if="attrs.applicationTypeLabel">{{ attrs.applicationTypeLabel }}</span>
                        <span v-if="attrs.studentNumber">· {{ attrs.studentNumber }}</span>
                    </div>
                    <div>
                        <component :is="statusTag" />
                    </div>
                </div>
                <div v-if="studentProfileUrl">
                    <BaseButton
                        classes="rounded-full" 
                        :title="`${$tChoice('trans.student', 1)} ${$tChoice('trans.profile', 1)}`"
                        :variant="ColorVariant.shade_outline" type="button" @click="router.visit(studentProfileUrl)">
                        <BaseIcon :name="IconName.user" :color="ColorVariant.shade" />
                    </BaseButton>
                </div>
            </div>

            <BaseAlert
                v-if="attrs.status === 'pending' && isStudentApplication && canReview"
                class="mb-6"
                :type="TypeVariant.info"
                :description="$t('hms.approve_payment_helper')"
            />

            <BaseAlert
                v-if="attrs.status === 'awaiting-payment'"
                class="mb-6"
                :type="TypeVariant.warning"
                :description="$t('hms.awaiting_payment_notice')"
            />

            <BaseAlert
                v-if="attrs.status === 'declined' && attrs.declineReason"
                class="mb-6"
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
                    :label="$tChoice('trans.calendar_year', 1)"
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
            <div v-if="isStudentApplication && eligibilityRules.length" class="mt-8 space-y-3">
                <HeadingSmall :title="$t('hms.eligibility_rules_heading')" />
                <ul class="space-y-2 text-sm">
                    <li
                        v-for="rule in eligibilityRules"
                        :key="rule.key"
                        :class="rule.passed ? 'text-green-700' : 'text-destructive'"
                    >
                        {{ rule.message }}
                    </li>
                </ul>
            </div>
            <div v-if="canReview" class="flex flex-wrap gap-2 my-6">
                <BaseButton 
                    type="button" 
                    :variant="ColorVariant.danger" 
                    @click="openDecline" 
                    :title="$t('hms.decline_application')"/>
                <BaseButton
                    v-if="isStudentApplication"
                    type="button"
                    @click="requestPayment"
                    :title="$t('hms.approve_application')"
                />
            </div>
        </template>
        <DeclineApplication @declined="loadApplication" />
    </PageContainer>
</template>

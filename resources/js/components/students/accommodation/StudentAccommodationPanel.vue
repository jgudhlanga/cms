<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import BaseAccordionItem from '@/components/core/accordion/BaseAccordionItem.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Empty from '@/components/core/util/Empty.vue';
import AccommodationApplicationSection from '@/components/students/accommodation/AccommodationApplicationSection.vue';
import AccommodationFeesSection from '@/components/students/accommodation/AccommodationFeesSection.vue';
import AccommodationLeavesSection from '@/components/students/accommodation/AccommodationLeavesSection.vue';
import AccommodationMyRoomSection from '@/components/students/accommodation/AccommodationMyRoomSection.vue';
import AccommodationNoticesSection from '@/components/students/accommodation/AccommodationNoticesSection.vue';
import AccommodationQueriesSection from '@/components/students/accommodation/AccommodationQueriesSection.vue';
import AccommodationSectionCard from '@/components/students/accommodation/AccommodationSectionCard.vue';
import AccommodationLeaveModal from '@/components/students/accommodation/modals/AccommodationLeaveModal.vue';
import AccommodationQueryModal from '@/components/students/accommodation/modals/AccommodationQueryModal.vue';
import { useStudentAccommodations } from '@/composables/students/useStudentAccommodations';
import { useStudentAccommodationServices } from '@/composables/students/useStudentAccommodationServices';
import { useStudentHostelApplicationForm } from '@/composables/students/useStudentHostelApplicationForm';
import { TypeVariant } from '@/enums/type-variants';
import type { Student } from '@/types/students';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import {
    Building,
    CircleHelp,
    Coins,
    DoorOpen,
    FileText,
    Megaphone,
} from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

interface Props {
    student: Student;
    context?: 'admin' | 'portal';
}

const props = withDefaults(defineProps<Props>(), {
    context: 'admin',
});

const studentId = computed(() => String(props.student?.id ?? ''));
const studentNumber = computed(() => props.student?.attributes?.studentNumber ?? undefined);
const {
    isLoading,
    loadError,
    applications,
    activeAllocation,
    openApplication,
    lookup,
    fees,
    roommates,
    canApply,
    applyBlockers,
    load,
    refresh,
} = useStudentAccommodations(
    () => studentId.value,
    () => studentNumber.value,
    () => props.context,
);

const services = useStudentAccommodationServices(() => studentId.value);

const { form, isSaving, saveValidationError, submit } = useStudentHostelApplicationForm(
    studentId,
    lookup,
    async () => {
        await refresh();
        await services.loadAll();
    },
);

const page = usePage();

const paymentError = computed(() => {
    if (props.context !== 'portal') {
        return null;
    }

    const flashError = (page.props.flash as { error?: string | null } | undefined)?.error;

    return typeof flashError === 'string' && flashError.length > 0 ? flashError : null;
});

onMounted(async () => {
    if (studentId.value) {
        await load();
        await services.loadAll();
    }
});

async function handleQueryCreate(payload: Parameters<typeof services.createQuery>[0]) {
    return services.createQuery(payload);
}

async function handleLeaveCreate(payload: Parameters<typeof services.createLeave>[0]) {
    return services.createLeave(payload);
}

const canCreateServices = computed(
    () => props.context === 'portal' && activeAllocation.value !== null,
);

const showNoAllocationHelper = computed(
    () => props.context === 'portal' && !activeAllocation.value,
);

const isPortal = computed(() => props.context === 'portal');

const myRoomBadge = computed(() => ({
    label: activeAllocation.value?.attributes?.roomName
        ?? trans('students.accommodation_no_room'),
    variant: activeAllocation.value ? 'default' as const : 'warning' as const,
}));

const applicationBadge = computed(() => {
    if (activeAllocation.value) {
        return {
            label: trans('students.accommodation_active_allocation'),
            variant: 'info' as const,
        };
    }

    if (openApplication.value) {
        return {
            label: openApplication.value.attributes.statusLabel
                ?? openApplication.value.attributes.status
                ?? trans('students.accommodation_application_status'),
            variant: 'warning' as const,
        };
    }

    return {
        label: String(applications.value.length),
        variant: 'default' as const,
    };
});

const feesBadge = computed(() => {
    if (!openApplication.value) {
        return { label: '—', variant: 'default' as const };
    }

    if (!fees.value) {
        return { label: '—', variant: 'default' as const };
    }

    return {
        label: fees.value.isFullyPaid ? fees.value.total : fees.value.due,
        variant: fees.value.isFullyPaid ? 'success' as const : 'warning' as const,
    };
});

const showFeeStats = computed(() => openApplication.value !== null);

const noticesBadge = computed(() => ({
    label: trans('students.accommodation_notices_count', { count: services.notices.value.length }),
    variant: 'default' as const,
}));

const queriesBadge = computed(() => ({
    label: String(services.queries.value.length),
    variant: 'default' as const,
}));

const leavesBadge = computed(() => ({
    label: String(services.leaves.value.length),
    variant: 'default' as const,
}));
</script>

<template>
    <div class="flex w-full min-w-0 flex-col py-4 font-sans">
        <BaseAlert
            v-if="paymentError"
            class="mb-4"
            :description="paymentError"
            :type="TypeVariant.danger"
        />

        <DataLoadingSpinner v-if="isLoading" />

        <div
            v-else-if="loadError"
            class="rounded-2xl border border-dashed border-border bg-card py-12"
        >
            <Empty :message="loadError" />
        </div>

        <div
            v-else-if="isPortal"
            class="flex w-full flex-col gap-4"
        >
            <AccommodationSectionCard
                :title="$t('students.accommodation_section_my_room')"
                :icon="Building"
                icon-tone="blue"
                :badge="myRoomBadge"
            >
                <AccommodationMyRoomSection
                    :allocation="activeAllocation"
                    :roommates="roommates"
                    :open-application="openApplication"
                    :fees="fees"
                    context="portal"
                />
            </AccommodationSectionCard>

            <AccommodationSectionCard
                :title="$t('students.accommodation_section_application')"
                :icon="FileText"
                icon-tone="blue"
                :badge="applicationBadge"
            >
                <AccommodationApplicationSection
                    :applications="applications"
                    :active-allocation="activeAllocation"
                    :open-application="openApplication"
                    :lookup="lookup"
                    :fees="fees"
                    :can-apply="canApply"
                    :apply-blockers="applyBlockers"
                    :form="form"
                    :is-saving="isSaving"
                    :save-validation-error="saveValidationError"
                    context="portal"
                    @submit="submit"
                />
            </AccommodationSectionCard>

            <AccommodationSectionCard
                :title="$t('students.accommodation_section_fees')"
                :icon="Coins"
                icon-tone="amber"
                :badge="feesBadge"
            >
                <AccommodationFeesSection
                    :fees="fees"
                    :show-stats="showFeeStats"
                    context="portal"
                />
            </AccommodationSectionCard>

            <AccommodationSectionCard
                :title="$t('students.accommodation_section_notices')"
                :icon="Megaphone"
                icon-tone="red"
                :badge="noticesBadge"
            >
                <AccommodationNoticesSection
                    :notices="services.notices.value"
                    :is-loading="services.isNoticesLoading.value"
                    context="portal"
                />
            </AccommodationSectionCard>

            <AccommodationSectionCard
                :title="$t('students.accommodation_section_queries')"
                :icon="CircleHelp"
                icon-tone="blue"
                :badge="queriesBadge"
            >
                <p
                    v-if="showNoAllocationHelper"
                    class="mb-4 text-sm text-muted-foreground"
                >
                    {{ $t('students.accommodation_no_room_assigned') }}
                </p>
                <AccommodationQueriesSection
                    :queries="services.queries.value"
                    :is-loading="services.isQueriesLoading.value"
                    :can-create="canCreateServices"
                    context="portal"
                />
            </AccommodationSectionCard>

            <AccommodationSectionCard
                :title="$t('students.accommodation_section_leaves')"
                :icon="DoorOpen"
                icon-tone="green"
                :badge="leavesBadge"
            >
                <p
                    v-if="showNoAllocationHelper"
                    class="mb-4 text-sm text-muted-foreground"
                >
                    {{ $t('students.accommodation_no_room_assigned') }}
                </p>
                <AccommodationLeavesSection
                    :leaves="services.leaves.value"
                    :is-loading="services.isLeavesLoading.value"
                    :can-create="canCreateServices"
                    context="portal"
                />
            </AccommodationSectionCard>
        </div>

        <BaseAccordion
            v-else
            class="w-full"
            :default-value="['my-room']"
        >
            <BaseAccordionItem
                value="my-room"
                :title="$t('students.accommodation_section_my_room')"
                :description="$t('students.accommodation_section_my_room_desc')"
            >
                <AccommodationMyRoomSection
                    :allocation="activeAllocation"
                    :roommates="roommates"
                    :open-application="openApplication"
                    :fees="fees"
                    :context="context"
                />
            </BaseAccordionItem>

            <BaseAccordionItem
                value="application"
                :title="$t('students.accommodation_section_application')"
                :description="$t('students.accommodation_section_application_desc')"
            >
                <AccommodationApplicationSection
                    :applications="applications"
                    :active-allocation="activeAllocation"
                    :open-application="openApplication"
                    :lookup="lookup"
                    :fees="fees"
                    :can-apply="canApply"
                    :apply-blockers="applyBlockers"
                    :form="form"
                    :is-saving="isSaving"
                    :save-validation-error="saveValidationError"
                    :context="context"
                    @submit="submit"
                />
            </BaseAccordionItem>

            <BaseAccordionItem
                value="fees"
                :title="$t('students.accommodation_section_fees')"
                :description="$t('students.accommodation_section_fees_desc')"
            >
                <AccommodationFeesSection :fees="fees" :show-stats="showFeeStats" :context="context" />
            </BaseAccordionItem>

            <BaseAccordionItem
                value="notices"
                :title="$t('students.accommodation_section_notices')"
                :description="$t('students.accommodation_section_notices_desc')"
            >
                <AccommodationNoticesSection
                    :notices="services.notices.value"
                    :is-loading="services.isNoticesLoading.value"
                    :context="context"
                />
            </BaseAccordionItem>

            <BaseAccordionItem
                value="queries"
                :title="$t('students.accommodation_section_queries')"
                :description="$t('students.accommodation_section_queries_desc')"
            >
                <AccommodationQueriesSection
                    :queries="services.queries.value"
                    :is-loading="services.isQueriesLoading.value"
                    :can-create="canCreateServices"
                    :context="context"
                />
            </BaseAccordionItem>

            <BaseAccordionItem
                value="leaves"
                :title="$t('students.accommodation_section_leaves')"
                :description="$t('students.accommodation_section_leaves_desc')"
            >
                <AccommodationLeavesSection
                    :leaves="services.leaves.value"
                    :is-loading="services.isLeavesLoading.value"
                    :can-create="canCreateServices"
                    :context="context"
                />
            </BaseAccordionItem>
        </BaseAccordion>

        <AccommodationQueryModal v-if="canCreateServices" :save="handleQueryCreate" />
        <AccommodationLeaveModal v-if="canCreateServices" :save="handleLeaveCreate" />
    </div>
</template>

<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseAccordion from '@/components/core/accordion/BaseAccordion.vue';
import BaseAccordionItem from '@/components/core/accordion/BaseAccordionItem.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Empty from '@/components/core/util/Empty.vue';
import AccommodationApplicationSection from '@/components/students/accommodation/AccommodationApplicationSection.vue';
import AccommodationDashboardSection from '@/components/students/accommodation/AccommodationDashboardSection.vue';
import AccommodationFeesSection from '@/components/students/accommodation/AccommodationFeesSection.vue';
import AccommodationLeavesSection from '@/components/students/accommodation/AccommodationLeavesSection.vue';
import AccommodationMyRoomSection from '@/components/students/accommodation/AccommodationMyRoomSection.vue';
import AccommodationNoticesSection from '@/components/students/accommodation/AccommodationNoticesSection.vue';
import AccommodationQueriesSection from '@/components/students/accommodation/AccommodationQueriesSection.vue';
import AccommodationLeaveModal from '@/components/students/accommodation/modals/AccommodationLeaveModal.vue';
import AccommodationQueryModal from '@/components/students/accommodation/modals/AccommodationQueryModal.vue';
import { useStudentAccommodations } from '@/composables/students/useStudentAccommodations';
import { useStudentAccommodationServices } from '@/composables/students/useStudentAccommodationServices';
import { useStudentHostelApplicationForm } from '@/composables/students/useStudentHostelApplicationForm';
import { TypeVariant } from '@/enums/type-variants';
import type { Student } from '@/types/students';
import { usePage } from '@inertiajs/vue3';
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

const openQueriesCount = computed(
    () => services.queries.value.filter((q) => ['open', 'in-progress'].includes(q.attributes.status)).length,
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

const canCreateServices = computed(() => props.context === 'portal');
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

        <BaseAccordion
            v-else
            class="w-full"
            :default-value="['dashboard']"
        >
            <BaseAccordionItem
                value="dashboard"
                :title="$t('students.accommodation_section_dashboard')"
                :description="$t('students.accommodation_section_dashboard_desc')"
            >
                <AccommodationDashboardSection
                    :active-allocation="activeAllocation"
                    :open-application="openApplication"
                    :fees="fees"
                    :open-queries-count="openQueriesCount"
                    :context="context"
                />
            </BaseAccordionItem>

            <BaseAccordionItem
                value="my-room"
                :title="$t('students.accommodation_section_my_room')"
                :description="$t('students.accommodation_section_my_room_desc')"
            >
                <AccommodationMyRoomSection
                    :allocation="activeAllocation"
                    :roommates="roommates"
                />
            </BaseAccordionItem>

            <BaseAccordionItem
                value="fees"
                :title="$t('students.accommodation_section_fees')"
                :description="$t('students.accommodation_section_fees_desc')"
            >
                <AccommodationFeesSection :fees="fees" />
            </BaseAccordionItem>

            <BaseAccordionItem
                value="notices"
                :title="$t('students.accommodation_section_notices')"
                :description="$t('students.accommodation_section_notices_desc')"
            >
                <AccommodationNoticesSection
                    :notices="services.notices.value"
                    :is-loading="services.isNoticesLoading.value"
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
        </BaseAccordion>

        <AccommodationQueryModal :save="handleQueryCreate" />
        <AccommodationLeaveModal :save="handleLeaveCreate" />
    </div>
</template>

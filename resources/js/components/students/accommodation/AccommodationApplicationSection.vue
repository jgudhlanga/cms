<script setup lang="ts">
import AccommodationApplicationForm from '@/components/students/accommodation/AccommodationApplicationForm.vue';
import AccommodationOpenApplicationCard from '@/components/students/accommodation/AccommodationOpenApplicationCard.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { TypeVariant } from '@/enums/type-variants';
import type {
    HostelAllocation,
    HostelApplication,
    HostelApplicationStudentLookupResponse,
    StudentAccommodationFeesResponse,
} from '@/types/hms';
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface FormShape {
    nextOfKinName: string;
    nextOfKinContact: string;
    checkIn: string;
    checkOut: string;
}

interface Props {
    applications: HostelApplication[];
    activeAllocation: HostelAllocation | null;
    openApplication: HostelApplication | null;
    lookup: HostelApplicationStudentLookupResponse | null;
    fees: StudentAccommodationFeesResponse | null;
    canApply: boolean;
    applyBlockers: string[];
    form: InertiaForm<FormShape>;
    isSaving: boolean;
    saveValidationError?: string | null;
    context: 'admin' | 'portal';
}

const props = defineProps<Props>();

const { formatDate } = useUtils();

const formatApplicationDateRange = (
    checkIn?: string | null,
    checkOut?: string | null,
    includeCheckOut = true,
): string => {
    const formattedCheckIn = checkIn ? formatDate(checkIn, 'L') : '—';

    if (!includeCheckOut) {
        return formattedCheckIn;
    }

    const formattedCheckOut = checkOut ? formatDate(checkOut, 'L') : '—';

    return `${formattedCheckIn} — ${formattedCheckOut}`;
};

const emit = defineEmits<{
    submit: [];
}>();

const sortedApplications = computed(() =>
    [...props.applications].sort(
        (a, b) =>
            new Date(b.attributes.createdAt ?? 0).getTime()
            - new Date(a.attributes.createdAt ?? 0).getTime(),
    ),
);

const showForm = computed(
    () =>
        !props.activeAllocation
        && !props.openApplication
        && props.lookup?.found !== false,
);

const showNotFoundMessage = computed(() => props.lookup?.found === false);

const isPortal = computed(() => props.context === 'portal');

const allocationDateRange = computed(() => {
    const attrs = props.activeAllocation?.attributes;
    return formatApplicationDateRange(attrs?.checkIn, attrs?.checkOut, !isPortal.value);
});

const currentApplication = computed(() => sortedApplications.value[0] ?? null);

const adminApplicationLink = (id: string | number) =>
    route('hostels.applications.show', { hostel_application: id });
</script>

<template>
    <div class="flex flex-col gap-4">
        <template v-if="activeAllocation && isPortal">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <BaseIcon name="history" class="h-4 w-4 shrink-0 text-muted-foreground" />
                <span class="font-medium text-foreground">
                    {{ currentApplication?.attributes.statusLabel ?? $t('students.accommodation_active_allocation') }}
                </span>
                <span class="font-semibold text-foreground">{{ allocationDateRange }}</span>
                <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                    {{ $t('students.accommodation_current') }}
                </span>
            </div>
            <p class="flex items-start gap-1.5 text-sm text-muted-foreground">
                <BaseIcon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                {{ $t('students.accommodation_has_allocation') }}
            </p>
        </template>

        <BaseAlert
            v-else-if="activeAllocation"
            :description="$t('students.accommodation_has_allocation')"
            :type="TypeVariant.info"
        />

        <AccommodationOpenApplicationCard
            v-else-if="openApplication"
            :open-application="openApplication"
            :fees="fees"
            :context="context"
            :show-progress="false"
        />

        <BaseAlert
            v-else-if="showNotFoundMessage"
            :description="lookup?.message ?? $t('hms.student_not_found')"
            :type="TypeVariant.warning"
        />

        <template v-else-if="applyBlockers.length">
            <BaseAlert
                v-for="(blocker, index) in applyBlockers"
                :key="index"
                :description="blocker"
                :type="TypeVariant.warning"
            />
        </template>

        <AccommodationApplicationForm
            v-if="showForm"
            :form="form"
            :eligibility="lookup?.eligibility"
            :semester-label="lookup?.semester?.label"
            :check-in="lookup?.semester?.checkIn"
            :check-out="lookup?.semester?.checkOut"
            :show-check-out="context === 'admin'"
            :can-submit="canApply"
            :is-saving="isSaving"
            :save-validation-error="saveValidationError"
            @submit="emit('submit')"
        />

        <div
            v-if="sortedApplications.length && !(activeAllocation && isPortal)"
            class="rounded-xl border border-border bg-card p-4"
        >
            <h4 class="mb-3 text-sm font-semibold text-foreground">{{ $t('students.accommodation_application_history') }}</h4>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="application in sortedApplications"
                    :key="application.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-muted/30 px-3 py-2 text-sm"
                >
                    <div>
                        <p class="font-medium text-foreground">
                            {{ application.attributes.statusLabel ?? application.attributes.status }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatApplicationDateRange(
                                application.attributes.checkIn,
                                application.attributes.checkOut,
                                context === 'admin',
                            ) }}
                        </p>
                    </div>
                    <a
                        v-if="context === 'admin'"
                        :href="adminApplicationLink(application.id)"
                        class="text-xs font-medium text-primary hover:underline"
                    >
                        {{ $t('students.accommodation_view_application') }}
                    </a>
                </li>
            </ul>
        </div>

        <p
            v-else-if="!showForm && !activeAllocation && !openApplication && !showNotFoundMessage"
            class="text-sm text-muted-foreground"
        >
            {{ $t('students.accommodation_no_applications') }}
        </p>
    </div>
</template>

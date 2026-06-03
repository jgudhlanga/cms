<script setup lang="ts">
import AccommodationApplicationForm from '@/components/students/accommodation/AccommodationApplicationForm.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';
import type { HostelAllocation, HostelApplication, HostelApplicationStudentLookupResponse } from '@/types/hms';
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
    canApply: boolean;
    applyBlockers: string[];
    form: InertiaForm<FormShape>;
    isSaving: boolean;
    saveValidationError?: string | null;
    context: 'admin' | 'portal';
}

const props = defineProps<Props>();

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

const adminApplicationLink = (id: string | number) =>
    route('hostels.applications.show', { hostel_application: id });
</script>

<template>
    <div class="flex flex-col gap-4">
        <BaseAlert
            v-if="activeAllocation"
            :description="$t('students.accommodation_has_allocation')"
            :type="TypeVariant.info"
        />

        <BaseAlert
            v-else-if="openApplication"
            :description="$t('students.accommodation_open_application', {
                status: openApplication.attributes.statusLabel ?? openApplication.attributes.status,
            })"
            :type="TypeVariant.info"
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

        <BaseAlert
            v-else-if="showForm && lookup && lookup.eligibilityPassed === false"
            :description="$t('students.accommodation_eligibility_not_met')"
            :type="TypeVariant.warning"
        />

        <AccommodationApplicationForm
            v-if="showForm"
            :form="form"
            :eligibility="lookup?.eligibility"
            :semester-label="lookup?.semester?.label"
            :check-in="lookup?.semester?.checkIn"
            :check-out="lookup?.semester?.checkOut"
            :can-submit="canApply"
            :is-saving="isSaving"
            :save-validation-error="saveValidationError"
            @submit="emit('submit')"
        />

        <div v-if="sortedApplications.length" class="rounded-xl border border-border bg-card p-4">
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
                            {{ application.attributes.checkIn }} — {{ application.attributes.checkOut }}
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

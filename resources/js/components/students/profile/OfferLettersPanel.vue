<script setup lang="ts">
import BaseTag from '@/components/core/util/BaseTag.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import Empty from '@/components/core/util/Empty.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStudentProfileApplications } from '@/composables/students/useStudentProfileApplications';
import { ColorVariant } from '@/enums/colors';
import type { Enrolment } from '@/types/enrolments';
import type { Student } from '@/types/students';
import { computed, onMounted } from 'vue';

interface Props {
    student: Student;
    offerLetterIntakePeriodIds?: Array<string | number>;
}

type OfferLetterGroup = {
    key: string;
    intakePeriod: string;
    level: string;
    year: string;
    applications: Enrolment[];
};

const props = withDefaults(defineProps<Props>(), {
    offerLetterIntakePeriodIds: () => [],
});

const { formatDate } = useUtils();
const { applications, isLoading, loadError, fetchStudentApplications } = useStudentProfileApplications();

const studentId = computed(() => String(props.student?.id ?? ''));

const offerLetterApplications = computed(() =>
    applications.value.filter((application) => application.attributes?.offerLetterAvailable),
);

const groupedOfferLetters = computed<OfferLetterGroup[]>(() => {
    const groups = new Map<string, OfferLetterGroup>();

    for (const application of offerLetterApplications.value) {
        const intakePeriod = application.attributes?.intakePeriod?.trim() || 'Unknown intake';
        const level = application.attributes?.level?.trim() || 'Unknown level';
        const year = application.attributes?.intakePeriodCalendarYear?.trim() || 'Unknown year';
        const key = [
            String(application.attributes?.intakePeriodId ?? 'unknown'),
            level,
            year,
        ].join('|');

        if (!groups.has(key)) {
            groups.set(key, {
                key,
                intakePeriod,
                level,
                year,
                applications: [],
            });
        }

        groups.get(key)?.applications.push(application);
    }

    return [...groups.values()]
        .map((group) => ({
            ...group,
            applications: group.applications.sort((left, right) =>
                String(left.attributes?.course ?? '').localeCompare(String(right.attributes?.course ?? '')),
            ),
        }))
        .sort((left, right) =>
            `${right.year} ${right.level} ${right.intakePeriod}`.localeCompare(
                `${left.year} ${left.level} ${left.intakePeriod}`,
            ),
        );
});

const isCurrentOffer = (application: Enrolment): boolean =>
    Boolean(application.attributes?.offerLetterCurrentIntake);

const issuedDate = (application: Enrolment): string => {
    const value = application.attributes?.offerLetterIssuedAt;

    return value ? formatDate(value, 'dd LLL yyyy') : 'Date unavailable';
};

onMounted(async () => {
    if (studentId.value) {
        await fetchStudentApplications(studentId.value);
    }
});
</script>

<template>
    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div class="mb-4">
            <h2 class="text-base font-semibold text-foreground">{{ $t('trans.ui_offer_letter') }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Offer letters are grouped by intake period, level, and year.
            </p>
        </div>

        <DataLoadingSpinner v-if="isLoading" />

        <div
            v-else-if="loadError"
            class="rounded-2xl border border-dashed border-border bg-card py-10"
        >
            <Empty :message="$t('students.applications_load_failure')" />
        </div>

        <div
            v-else-if="groupedOfferLetters.length === 0"
            class="rounded-2xl border border-dashed border-border bg-card py-10"
        >
            <Empty :message="$t('trans.ui_offer_letter')" />
            <p class="mt-2 text-center text-sm text-muted-foreground">
                No offer letters are available yet.
            </p>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="group in groupedOfferLetters"
                :key="group.key"
                class="rounded-xl border border-border"
            >
                <div class="border-b border-border bg-muted/30 px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="text-sm font-semibold text-foreground">{{ group.intakePeriod }}</h3>
                            <p class="text-xs text-muted-foreground">{{ group.level }} · {{ group.year }}</p>
                        </div>
                        <BaseTag
                            v-if="group.applications.some(isCurrentOffer)"
                            :title="$t('students.current_intake')"
                            :variant="ColorVariant.success"
                            classes="cursor-default"
                        />
                    </div>
                </div>

                <div class="divide-y divide-border">
                    <div
                        v-for="application in group.applications"
                        :key="application.id"
                        class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <p class="font-medium text-foreground">{{ application.attributes?.course }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ application.attributes?.modeOfStudy }} · Issued {{ issuedDate(application) }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <BaseTag
                                :title="isCurrentOffer(application) ? $t('students.current_intake') : 'Past intake'"
                                :variant="isCurrentOffer(application) ? ColorVariant.success : ColorVariant.shade"
                                classes="cursor-default"
                            />
                            <a
                                v-if="application.attributes?.offerLetterAvailable || application.attributes?.offerLetterDownloadUrl"
                                :href="application.attributes?.offerLetterDownloadUrl || route('documents.offer-letter', { student_application: application.id })"
                                class="inline-flex items-center rounded-full border border-primary px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ $t('trans.ui_offer_letter') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

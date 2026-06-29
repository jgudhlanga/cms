import type { Enrolment } from '@/types/enrolments';
import { computed, type Ref } from 'vue';
import { trans, trans_choice } from 'laravel-vue-i18n';

export type IntakeApplicationGroup = {
    intakePeriodId: string;
    label: string;
    description?: string;
    sortKey: string;
    applications: Enrolment[];
};

function intakeGroupKey(application: Enrolment): string {
    const id = application.attributes?.intakePeriodId;

    return id !== undefined && id !== null && String(id) !== '' ? String(id) : 'unknown';
}

function intakeGroupLabel(application: Enrolment): string {
    const name = application.attributes?.intakePeriod?.trim();

    if (name) {
        return name;
    }

    return trans('students.unknown_intake_period');
}

function intakeGroupSortKey(application: Enrolment): string {
    if (application.attributes?.intakePeriodStartDate) {
        return application.attributes.intakePeriodStartDate;
    }

    return application.attributes?.createdAt ?? '';
}

export function groupApplicationsByIntakePeriod(applications: Enrolment[]): IntakeApplicationGroup[] {
    const groups = new Map<string, IntakeApplicationGroup>();

    for (const application of applications) {
        const key = intakeGroupKey(application);
        const existing = groups.get(key);

        if (existing) {
            existing.applications.push(application);
            continue;
        }

        const calendarYear = application.attributes?.intakePeriodCalendarYear;

        groups.set(key, {
            intakePeriodId: key,
            label: intakeGroupLabel(application),
            description: calendarYear ? String(calendarYear) : undefined,
            sortKey: intakeGroupSortKey(application),
            applications: [application],
        });
    }

    return [...groups.values()].sort((a, b) => b.sortKey.localeCompare(a.sortKey));
}

export function useApplicationsByIntakePeriod(
    applications: Ref<Enrolment[]>,
    activeIntakePeriodIds?: Ref<Array<string | number>>,
) {
    const resolvedActiveIntakePeriodIds = activeIntakePeriodIds ?? computed(() => []);

    const groups = computed(() => groupApplicationsByIntakePeriod(applications.value));

    const defaultOpenIntakeIds = computed(() => {
        const activeIds = resolvedActiveIntakePeriodIds.value.map((id) => String(id));
        const activeGroup = groups.value.find((group) => activeIds.includes(group.intakePeriodId));

        if (activeGroup) {
            return [activeGroup.intakePeriodId];
        }

        return [];
    });

    const intakeGroupDescription = (group: IntakeApplicationGroup): string => {
        const count = group.applications.length;
        const parts: string[] = [];

        if (group.description) {
            parts.push(group.description);
        }

        parts.push(trans_choice('students.intake_applications_count', count, { count }));

        return parts.join(' · ');
    };

    return {
        groups,
        defaultOpenIntakeIds,
        intakeGroupDescription,
    };
}

import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';

export const ENROLMENT_STATUS_FROM_VALUES = ['dashboard', 'enrolments'] as const;

export type EnrolmentStatusFrom = (typeof ENROLMENT_STATUS_FROM_VALUES)[number];

export function isEnrolmentStatusFrom(value: string | null | undefined): value is EnrolmentStatusFrom {
    return value === 'dashboard' || value === 'enrolments';
}

export function parseEnrolmentStatusFrom(value: string | null | undefined): EnrolmentStatusFrom {
    return value === 'dashboard' ? 'dashboard' : 'enrolments';
}

export function enrolmentStatusOriginBackUrl(from: EnrolmentStatusFrom, intakePeriodId?: string | null): string {
    const params = intakePeriodId ? { intake_period_id: intakePeriodId } : {};

    return from === 'dashboard' ? route('dashboard', params) : route('enrolments.index', params);
}

export type DepartmentApplicationsUrlOptions = {
    institutionDepartmentId: string | number;
    type: string;
    intakePeriodId?: string | null;
    modeOfStudyId?: string | null;
    from?: string | null;
};

export function buildDepartmentApplicationsUrl(options: DepartmentApplicationsUrlOptions): string {
    return mergeQueryParamsIntoRequestPath(
        route('enrolments.department-applications', {
            institution_department: options.institutionDepartmentId,
        }),
        {
            type: options.type,
            intake_period_id: options.intakePeriodId,
            mode_of_study_id: options.modeOfStudyId,
            from: isEnrolmentStatusFrom(options.from) ? options.from : undefined,
        },
    );
}

export function enrolmentStatusFromQuery(query: { from?: string | null }): { from?: EnrolmentStatusFrom } {
    if (!isEnrolmentStatusFrom(query.from)) {
        return {};
    }

    return { from: query.from };
}

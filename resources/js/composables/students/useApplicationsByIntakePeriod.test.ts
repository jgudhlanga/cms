import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

vi.mock('laravel-vue-i18n', () => ({
    trans: (key: string) => key,
    trans_choice: (key: string) => key,
}));

import {
    groupApplicationsByIntakePeriod,
    useApplicationsByIntakePeriod,
} from '@/composables/students/useApplicationsByIntakePeriod';
import type { Enrolment } from '@/types/enrolments';

function makeApplication(
    id: string | number,
    intakePeriodId: string | number,
    options: {
        intakePeriodStartDate?: string;
        workflowStep?: string;
        createdAt?: string;
    } = {},
): Enrolment {
    return {
        type: 'enrolment',
        id: String(id),
        attributes: {
            intakePeriodId,
            intakePeriod: `Intake ${intakePeriodId}`,
            intakePeriodStartDate: options.intakePeriodStartDate,
            studentId: '1',
            studentName: 'Test Student',
            modeOfStudyId: 1,
            modeOfStudy: 'Full Time',
            phoneNumber: '000',
            email: 'test@example.com',
            institutionDepartmentId: 1,
            departmentLevelId: 1,
            departmentCourseId: 1,
            department: 'Engineering',
            level: 'ND',
            levelId: 1,
            allowedApplicationsPerLevel: 1,
            hasEnrolmentRequirements: false,
            course: 'Civil Engineering',
            applicationTrackingNumber: 'APP001',
            requiredExamSittingCount: 0,
            registrationFeeConfirmed: false,
            tuitionFeeConfirmed: false,
            requiredLevelCompleted: false,
            readWriteAcknowledged: false,
            createdAt: options.createdAt ?? '2025-01-01',
            deletedAt: '',
            updatedAt: options.createdAt ?? '2025-01-01',
        },
        relationships: {
            departmentWorkflowStep: {
                type: 'department-workflow-step',
                id: '1',
                attributes: {
                    workflowStep: options.workflowStep ?? 'Review',
                },
            },
        } as Enrolment['relationships'],
    };
}

describe('useApplicationsByIntakePeriod', () => {
    describe('defaultOpenIntakeIds', () => {
        it('opens the most recent intake group by default', () => {
            const applications = ref([
                makeApplication(1, 10, { intakePeriodStartDate: '2025-01-01' }),
                makeApplication(2, 20, { intakePeriodStartDate: '2026-08-01' }),
            ]);
            const activeIntakePeriodIds = ref([10]);

            const { defaultOpenIntakeIds } = useApplicationsByIntakePeriod(applications, activeIntakePeriodIds);

            expect(defaultOpenIntakeIds.value).toEqual(['20']);
        });

        it('opens the only intake group when there is a single group', () => {
            const applications = ref([makeApplication(1, 10, { intakePeriodStartDate: '2025-01-01' })]);
            const activeIntakePeriodIds = ref([20]);

            const { defaultOpenIntakeIds } = useApplicationsByIntakePeriod(applications, activeIntakePeriodIds);

            expect(defaultOpenIntakeIds.value).toEqual(['10']);
        });

        it('returns an empty array when there are no applications', () => {
            const applications = ref<Enrolment[]>([]);
            const activeIntakePeriodIds = ref([20]);

            const { defaultOpenIntakeIds } = useApplicationsByIntakePeriod(applications, activeIntakePeriodIds);

            expect(defaultOpenIntakeIds.value).toEqual([]);
        });
    });

    describe('groupApplicationsByIntakePeriod', () => {
        it('sorts applications within a group with positive statuses first', () => {
            const groups = groupApplicationsByIntakePeriod([
                makeApplication(1, 10, { workflowStep: 'Rejected', createdAt: '2026-01-03' }),
                makeApplication(2, 10, { workflowStep: 'Review', createdAt: '2026-01-01' }),
                makeApplication(3, 10, { workflowStep: 'Enrolled', createdAt: '2026-01-02' }),
                makeApplication(4, 10, { workflowStep: 'Accepted', createdAt: '2026-01-04' }),
            ]);

            expect(groups).toHaveLength(1);
            expect(groups[0].applications.map((application) => application.id)).toEqual(['3', '4', '2', '1']);
        });
    });
});

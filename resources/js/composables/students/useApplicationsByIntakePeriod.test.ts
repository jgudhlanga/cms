import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

vi.mock('laravel-vue-i18n', () => ({
    trans: (key: string) => key,
    trans_choice: (key: string) => key,
}));

import { useApplicationsByIntakePeriod } from '@/composables/students/useApplicationsByIntakePeriod';
import type { Enrolment } from '@/types/enrolments';

function makeApplication(intakePeriodId: string | number, intakePeriodStartDate?: string): Enrolment {
    return {
        type: 'enrolment',
        id: String(intakePeriodId),
        attributes: {
            intakePeriodId,
            intakePeriod: `Intake ${intakePeriodId}`,
            intakePeriodStartDate,
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
            createdAt: '2025-01-01',
            deletedAt: '',
            updatedAt: '2025-01-01',
        },
    };
}

describe('useApplicationsByIntakePeriod', () => {
    describe('defaultOpenIntakeIds', () => {
        it('opens only the active intake group when the student has an application for it', () => {
            const applications = ref([
                makeApplication(10, '2025-01-01'),
                makeApplication(20, '2026-08-01'),
            ]);
            const activeIntakePeriodIds = ref([20]);

            const { defaultOpenIntakeIds } = useApplicationsByIntakePeriod(applications, activeIntakePeriodIds);

            expect(defaultOpenIntakeIds.value).toEqual(['20']);
        });

        it('returns an empty array when only past intake applications exist', () => {
            const applications = ref([makeApplication(10, '2025-01-01')]);
            const activeIntakePeriodIds = ref([20]);

            const { defaultOpenIntakeIds } = useApplicationsByIntakePeriod(applications, activeIntakePeriodIds);

            expect(defaultOpenIntakeIds.value).toEqual([]);
        });

        it('returns an empty array when there are no applications', () => {
            const applications = ref<Enrolment[]>([]);
            const activeIntakePeriodIds = ref([20]);

            const { defaultOpenIntakeIds } = useApplicationsByIntakePeriod(applications, activeIntakePeriodIds);

            expect(defaultOpenIntakeIds.value).toEqual([]);
        });
    });
});

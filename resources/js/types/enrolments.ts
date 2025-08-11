import { DepartmentApplicationStep } from '@/types/department-meta-data';

export type Enrolment = {
    type: string;
    id: string | number;
    attributes: {
        studentId: string | number;
        studentName: string;
        studentNumber?: string;
        institutionDepartmentId: string | number;
        departmentLevelId: string | number;
        departmentCourseId: string | number;
        department: string;
        level: string;
        course: string;
        applicationTrackingNumber: string;
        createdAt: string;
        deletedAt: string;
        updatedAt: string;
    };
    relationships?: {
        departmentWorkflowStep: DepartmentApplicationStep;
    };
};

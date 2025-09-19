import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { Ledger } from '@/types/integrations';

export type Enrolment = {
    type: string;
    id: string | number;
    attributes: {
        studentId: string | number;
        studentName: string;
        studentNumber?: string;
        modeOfStudyId: string | number;
        modeOfStudy: string;
        institutionDepartmentId: string | number;
        departmentLevelId: string | number;
        departmentCourseId: string | number;
        department: string;
        level: string;
        course: string;
        applicationTrackingNumber: string;
        requiredExamSittingCount: string | number;
        registrationFeePaid: boolean;
        tuitionFeePaid: boolean;
        registrationFeeConfirmed: boolean;
        tuitionFeeConfirmed: boolean;
        createdAt: string;
        deletedAt: string;
        updatedAt: string;
    };
    relationships?: {
        registrationReceipt?: Ledger;
        tuitionReceipt?: Ledger;
        oLevelResults: AcademicOLevelResult[];
        departmentWorkflowStep: DepartmentApplicationStep;
    };
};

export type AcademicOLevelResult = {
    type: string;
    id: string | number;
    attributes: {
        academicLevelId: string | number;
        academicLevel: string;
        subjectId: string | number;
        subject: string;
        examYear: string | number;
        examSitting: string | number;
        gradeId: string | number;
        grade: string;
        gradePosition: string | number;
        remarks: string;
        createdAt: string;
        updatedAt: string;
        deletedAt: string | null;
    };
};

export type BulkApplicationApprovalParams = {
    intake_period_id: string | number;
    department_level_id: string | number;
    current_step_id: string | number;
    new_step_id: string | number;
};

export type PaymentProofPreview = {
    enrolmentId: string | number;
    url: string;
};

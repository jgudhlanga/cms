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
        levelId: string | number;
        allowedApplicationsPerLevel: string | number;
        hasEnrolmentRequirements: boolean;
        course: string;
        applicationTrackingNumber: string;
        requiredExamSittingCount: string | number;
        registrationFeeConfirmed: boolean;
        tuitionFeeConfirmed: boolean;
        requiredLevelCompleted: boolean;
        readWriteAcknowledged: boolean;
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
    mode_of_study_id: string;
    new_step_id: string | number;
};

export type BulkUpdatePaymentStatus = {
    intake_period_id: string;
    department_level_id: string;
    mode_of_study_id: string;
    step: DepartmentApplicationStep | null;
};

export type BulkUpdatePaymentStatusParams = {
    intake_period_id: string;
    department_level_id: string;
    mode_of_study_id: string;
    current_step_id: string;
    field_to_update: 'registration_fee_confirmed' | 'tuition_fee_confirmed';
    field_value: boolean;
};

export type PaymentProofPreview = {
    enrolmentId: string | number;
    url: string;
};

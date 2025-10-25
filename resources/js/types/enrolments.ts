import { DepartmentApplicationStep } from '@/types/department-meta-data';
import { Ledger } from '@/types/integrations';
import { User } from '@/types/users';

export type Enrolment = {
    type: string;
    id: string | number;
    attributes: {
        studentId: string | number;
        studentName: string;
        studentNumber?: string;
        modeOfStudyId: string | number;
        modeOfStudy: string;
        phoneNumber: string;
        email: string;
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
        disabilityStatus?: 'yes' | 'no' | 'prefer_not_to_say' | null;
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

export type OLevelSubjectResult = {
    type: string;
    id: string | number;
    attributes: {
        studentId: string | number;
        resultId: string | number;
        subject: string;
        examYear: string | number;
        examSitting: string | number;
        gradeId: string | number;
        grade: string;
    };
};

export type OLevelSubjectResultParams = {
    subject_id: string | number;
    exam_year: string | number;
    exam_sitting: string | number;
    grade_id: string | number;
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

export type EnrolmentSearchParams = {
    institution_department_id: string;
    department_level_id: string;
    department_course_id: string;
    intake_period_id: string;
    mode_of_study_id: string;
};

export type EnrolmentLookup = {
    user?: User;
    studentId?: string | number;
    hasPaidApplicationFee: boolean;
    hasAdminRole: boolean;
    eligibleForEnrolment: boolean;
    currentLevel: string | null;
    currentProgramCount: number | null;
    statusCode: number | null;
    message?: string | null;
};

export interface OLeveResult {
    resultId: number;
    subjectId: number;
    examYear: string;
    examSitting: string;
    gradeId: number;
    subject: string;
    grade: string;
}

export interface EnrolmentApplication {
    applicationId: number;
    applicationTrackingNumber: string;
    applicationDate: string;

    studentId: string;
    studentName: string;
    studentNumber: string;
    email: string | null;
    phoneNumber: string | null;

    gender: string | null;
    disabilityStatus: string | null;
    workflowStep: string | null;

    receiptId: number | null;
    receiptAmount: number | null;

    examSittingsCount: number;
    firstExamYear: string | null;
    inClassList: boolean;
    classListType: string | null;

    academicResults: OLeveResult[];
    totalScore: number;
    hasNoPayment: boolean;
    hasInvalidGrade: boolean;
}

export interface EnrolmentApplicationGroup {
    disabled: EnrolmentApplication[];
    females: EnrolmentApplication[];
    males: EnrolmentApplication[];
    others: EnrolmentApplication[];
}

export interface Pagination {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    links: any[];
}

export interface EnrolmentGroupResponse {
    pagination: Pagination;
    groups: EnrolmentApplicationGroup;
}
export type ClassSizeSlot = {
    disabled: number;
    females: number;
    males: number;
};

export type ClassListParams = {
    class_list: string[]|null;
    waiting_list: string[]|null;
    type: 'provisional' | 'verified' | 'waitlisted' | 'final' | 'failed';
};

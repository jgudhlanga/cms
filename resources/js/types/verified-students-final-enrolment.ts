export type VerifiedStudentPaymentEligibility = 'eligible' | 'no_payment' | 'missing_student_number';

export interface VerifiedStudentForFinalEnrolment {
    type: string;
    id: number;
    attributes: {
        name: string | null;
        email: string | null;
        studentNumber: string | null;
        idNumber: string | null;
        department: string | null;
        course: string | null;
        level: string | null;
        classListId: number | null;
        studentId: number | null;
        paymentEligibility: VerifiedStudentPaymentEligibility;
        hasMatchingPayment: boolean;
    };
}

export interface VerifiedStudentsFinalEnrolmentSummary {
    total: number;
    eligible: number | null;
    noPayment: number | null;
    missingStudentNumber: number;
    paymentSummaryReady?: boolean;
}

export interface VerifiedStudentsFinalEnrolmentPaymentWindow {
    startDate: string;
    endDate: string;
}

export type VerifiedStudentPaymentStatusFilter =
    | 'all'
    | 'eligible'
    | 'no_payment'
    | 'missing_student_number';

export interface VerifiedStudentsFinalEnrolmentFiltersState {
    search?: string;
    department?: number[];
    level?: number[];
    course?: number[];
    payment_status?: VerifiedStudentPaymentStatusFilter;
}

export interface BulkFinaliseDispatchPayload {
    student_application_ids?: number[];
    force_finalise?: boolean;
}

export interface BulkFinaliseRunProgress {
    status: 'pending' | 'running' | 'completed' | 'failed';
    processed: number;
    total: number;
    successful: number;
    failed: number;
    message: string | null;
}

export interface BulkFinaliseDispatchResponse {
    runId: string;
    startDate: string;
    endDate: string;
    message: string;
}

export interface VerifiedStudentsFinalEnrolmentApiResponse {
    data?: VerifiedStudentForFinalEnrolment[];
    meta?: Record<string, unknown> | null;
    links?: Record<string, unknown> | null;
    paymentWindow?: VerifiedStudentsFinalEnrolmentPaymentWindow;
    summary?: VerifiedStudentsFinalEnrolmentSummary;
}

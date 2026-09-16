export type StudyPositionState = 'unconfirmed' | 'follow_up' | 'needs_review' | 'confirmed';

export type StudyPositionAnswer = 'phase' | 'not_sure' | 'wrong_programme';

export type StudyPositionSyncStatus = 'applied' | 'unchanged' | 'needs_review' | 'not_applicable';

export interface StudyPositionPhase {
    id: number;
    name: string;
    label: string;
    position: number;
    kind: string;
}

export interface StudyPositionConfirmation {
    answer: StudyPositionAnswer;
    answerLabel: string;
    phase: StudyPositionPhase | null;
    source: string;
    sourceLabel: string;
    syncStatus: StudyPositionSyncStatus;
    confirmedAt: string | null;
    // Admin payload only.
    previousPhase?: StudyPositionPhase | null;
    syncNote?: string | null;
    reason?: string | null;
    confirmedBy?: string | null;
    needsReview?: boolean;
}

export interface StudyPositionItem {
    enrolmentId: number;
    programme: {
        label: string;
        course: string | null;
        level: string | null;
        department: string | null;
        modeOfStudy: string | null;
    };
    period: {
        id: number;
        label: string;
        slotName: string;
        calendarYear: string;
    };
    systemPhase: StudyPositionPhase | null;
    options: StudyPositionPhase[];
    confirmation: StudyPositionConfirmation | null;
    state: StudyPositionState;
    drift: boolean;
    studentCanAnswer: boolean;
    canConfirm: boolean;
}

export interface StudyPositionStatus {
    state: StudyPositionState | null;
    required: boolean;
    periodLabel: string | null;
    items: StudyPositionItem[];
}

export interface StudyPositionSummaryItem {
    enrolmentId: number;
    programme: string;
    state: StudyPositionState;
    answeredPhase: string | null;
    systemPhase: string | null;
}

/** Shared on every portal page as `studyPosition`. */
export interface StudyPositionSummary {
    state: StudyPositionState | null;
    required: boolean;
    periodLabel: string | null;
    items: StudyPositionSummaryItem[];
}

export interface StudyPositionBannerItem {
    programme: string;
    state: StudyPositionState;
    answeredPhase: string | null;
    systemPhase: string | null;
    answerLabel?: string | null;
    syncNote?: string | null;
    drift?: boolean;
}

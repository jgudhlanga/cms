export type ExaminationFilterOption = {
    value: string;
    label: string;
};

export type ExaminationSearchFiltersState = {
    session?: string | null;
    discipline?: string | null;
    subject_code?: string | null;
    surname?: string | null;
    first_names?: string | null;
    candidate_number?: string | null;
};

export type ExaminationDashboardFiltersState = {
    session?: string | null;
    discipline?: string | null;
    subject_code?: string | null;
    compare_session?: string | null;
};

export type ExaminationFilterOptions = {
    sessions: ExaminationFilterOption[];
    disciplines: ExaminationFilterOption[];
    subjects: ExaminationFilterOption[];
    compareSessions?: ExaminationFilterOption[];
};

export type ExaminationStatusCounts = {
    ABSENT: number;
    AWARD: number;
    DEFERRED: number;
    DISQUALIFIED: number;
    PROCEED: number;
    REFERRED: number;
};

export type ExaminationStatusLabels = {
    ABSENT: string;
    AWARD: string;
    DEFERRED: string;
    DISQUALIFIED: string;
    PROCEED: string;
    REFERRED: string;
};

export type ExaminationChartLabels = {
    session: string;
    compareSession: string;
    passRate: string;
    modulePassPrimary: string;
    modulePassCompare: string;
    moduleImproved: string;
    moduleDeclined: string;
    moduleUnchanged: string;
};

export type ExaminationModuleComparison = {
    subjectCode: string;
    subject: string | null;
    primaryPassRate: number | null;
    comparePassRate: number | null;
    delta: number | null;
    trend: 'improved' | 'declined' | 'unchanged';
};

export type ExaminationComparison = {
    primaryPassRate: number | null;
    comparePassRate: number | null;
    modules: ExaminationModuleComparison[];
};

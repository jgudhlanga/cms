export type ProgrammeUsageRecord = {
    application_id: number;
    student_enrolment_id: number | null;
    student_name: string;
    institution_department_id: number | null;
    department: string | null;
    department_level_id: number | null;
    level: string | null;
    department_course_id: number | null;
    course: string | null;
    mode_of_study_id: number | null;
    mode_of_study: string | null;
    intake_period: string | null;
    has_enrolment: boolean;
};

export type ReassignProgrammeSource = {
    departmentCourseId?: number | string | null;
    departmentLevelId?: number | string | null;
    modeOfStudyIds?: Array<number | string>;
};

import { IntakePeriod } from '@/types/institution';

export type AcademicCalendarOption = {
    type: string;
    id: string;
    attributes: {
        name: string;
        description: string;
    };
};
export type AcademicCalendar = {
    type: string;
    id: string;
    attributes: {
        name: string;
        academicCalendarOptionId: string;
        calendarYear: string;
        openingDate: string;
        closingDate: string;
        intakePeriods: string;
    };
    relationships: {
        intakePeriods: IntakePeriod[];
    };
};

export type AcademicCalendarParams = {
    academic_calendar_option_id: string | number | null;
    calendar_year: string | null;
    opening_date: string | null;
    closing_date: string | null;
    intake_period_ids?: [];
};

export type DepartmentCourseClassCount = {
    institutionDepartmentId: string;
    departmentCourseId: string;
    courseName: string;
    levels: ClassLevelSummary[];
};

export type ClassLevelSummary = {
    departmentLevelId: string | number;
    levelName: string;
    studentsPerClass: string | number | null;
    classConfigId: string | number | null;
    totalFinalClass: string | number | null;
};

export type AcademicClassConfigPayload = {
    academic_calendar_id: string | number | null;
    department_level_id: string | number | null;
    department_course_id: string | number | null;
    mode_of_study_id: string | number | null;
    students_per_class: string | number | null;
};

export type ClassConfig = {
    type: string;
    id: string;
    attributes: {
        studentsPerClass: string | number | null;
        academicCalendar: string | null;
        institutionDepartment: string | null;
        departmentCourse: string | null;
        departmentLevel: string | null;
        modeOfStudy: string | null;
    };
};

export type AcademicCalendarClassPreviewStudent = {
    studentProgramId: number;
    studentId: number;
    applicationTrackingNumber: string | null;
    name: string;
};

export type AcademicCalendarClassPreview = {
    academicCalendarClassId: number | null;
    name: string;
    studentCount: number;
    students: AcademicCalendarClassPreviewStudent[];
};

export type AcademicCalendarClassMetadata = {
    key: string;
    label: string;
    value: string;
};

export type AcademicCalendarClassDetail = {
    id: number;
    name: string;
    description: string | null;
    studentCount: number;
    students: AcademicCalendarClassPreviewStudent[];
    metadata: AcademicCalendarClassMetadata[];
};

export type AcademicCalendarClassGenerationContext = {
    institutionDepartmentId: number;
    academicCalendarId: number;
    departmentLevelId: number | null;
    departmentCourseId: number | null;
    modeOfStudyId: number | null;
    classConfigId: number | null;
    studentsPerClass: number | null;
    finalStudentCount: number;
};
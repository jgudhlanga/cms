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
    totalFinalClass: string | number | null;
};

export type AcademicClassConfigPayload = {
    academic_calendar_id: string | number | null;
    department_level_id: string | number | null;
    department_course_id: string | number | null;
    mode_of_study_id: string | number | null;
    students_per_class: string | number | null;
};

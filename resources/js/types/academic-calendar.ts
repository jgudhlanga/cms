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
    };
};

export type AcademicCalendarParams = {
    academic_calendar_option_id: string | number | null;
    calendar_year: string | null;
    opening_date: string | null;
    closing_date: string | null;
};

export type DepartmentCourseClassCount = {
    institutionDepartmentId: string;
    departmentCourseId: string;
    courseName: string;
    levels: ClassLevelSummary[];
};

export type ClassLevelSummary = {
    departmentLevelId: string;
    levelName: string;
    classSize: number;
    totalEnrolledStudents: number;
};

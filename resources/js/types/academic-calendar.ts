export type AcademicCalendar = {
    type: string;
    id: string;
    attributes: {
        name: string;
        calendarYear: string;
        openingDate: string;
        closingDate: string;
        calendarType: AcademicCalendarType;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type AcademicCalendarParams = {
    name: string;
    calendar_year: string | null;
    calendar_type: AcademicCalendarType | null;
    opening_date: string | null;
    closing_date: string | null;
    description?: string;
};
export enum AcademicCalendarType {
    SEMESTER = 'semester',
    TRIMESTER = 'trimester',
    QUADMESTER = 'quadmester',
    QUARTER = 'quarter',
    BLOCK = 'block',
    MODULAR = 'modular',
    MINIMESTER = 'minimester',
    OTHER = 'other',
}

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

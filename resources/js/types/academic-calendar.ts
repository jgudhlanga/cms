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
    calendar_year: AcademicCalendarType;
    calendar_type: AcademicCalendarType;
    opening_date: string;
    closing_date: string;
    description?: string;
};
export enum AcademicCalendarType {
    SEMESTER = "semester",
    TRIMESTER = "trimester",
    QUADMESTER = "quadmester",
    QUARTER = "quarter",
    BLOCK = "block",
    MODULAR = "modular",
    MINIMESTER = "minimester",
    OTHER = "other",
}

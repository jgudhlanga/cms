export type AcademicCalendar = {
    type: string;
    id: string;
    attributes: {
        calendarYear: string;
        type: 'term' | 'semester' | 'abma';
        openingDate: string;
        closingDate: string;
    };
};

export type AcademicCalendarParams = {
    calendar_year: string | null;
    type: 'term' | 'semester' | 'abma' | null;
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
    departmentLevelId: string | number;
    levelName: string;
    /** From `Level.calendar_type` — drives which academic year options apply (semester / term / abma). */
    calendarType?: 'term' | 'semester' | 'abma' | null;
    studentsPerClass: string | number | null;
    classConfigId: string | number | null;
    classesCount: number;
    totalnClass: string | number | null;
    totalFinalList: string | number | null;
    academicYearOption: string | null;
    academicYearOptionId: string | number | null;
};

export type AcademicClassConfigPayload = {
    academic_calendar_id: string | number | null;
    department_level_id: string | number | null;
    department_course_id: string | number | null;
    mode_of_study_id: string | number | null;
    students_per_class: string | number | null;
    calendarType?: 'term' | 'semester' | 'abma' | null;
    academic_year_option_id?: string | number | null;
};

export type ClassConfig = {
    type: string;
    id: string;
    attributes: {
        studentsPerClass: string | number | null;
        calendarYear: string | null;
        institutionDepartment: string | null;
        departmentCourse: string | null;
        departmentLevel: string | null;
        modeOfStudy: string | null;
    };
};

export type AcademicCalendarClassPreviewStudent = {
    studentEnrolmentId: number;
    studentId: number;
    applicationTrackingNumber: string | null;
    studentNumber?: string | null;
    gender?: string | null;
    name: string;
};

export type AcademicCalendarClassPreview = {
    academicCalendarClassId: number | null;
    name: string;
    studentCount: number;
    genderCounts: {
        male: number;
        female: number;
        unknown: number;
    };
    students: AcademicCalendarClassPreviewStudent[];
};

export type AcademicCalendarClassMoveTarget = {
    id: number;
    name: string;
};

export type AcademicCalendarClassDetail = {
    id: number;
    name: string;
    description: string | null;
    studentCount: number;
    students: AcademicCalendarClassPreviewStudent[];
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
    newFinalStudentCount: number;
    newStudentGenderCounts: {
        male: number;
        female: number;
        unknown: number;
    };
    hasExistingClasses: boolean;
    /** Timetable classes that already have at least one student assignment (matches department API classesCount). */
    populatedExistingClassCount: number;
};
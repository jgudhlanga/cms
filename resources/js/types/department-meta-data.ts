export type DepartmentLevel = {
    type?: string;
    id?: string | number;
    attributes: {
        institutionDepartmentId: string | number;
        levelId: string | number;
        level: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type DepartmentLevelParams = {
    level_ids: Array<string | undefined | null> | null,
};

export type DepartmentCourse = {
    type?: string;
    id?: string | number;
    attributes: {
        institutionDepartmentId: string | number;
        courseId: string | number;
        course: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type DepartmentCourseParams = {
    course_ids: Array<string | undefined | null> | null,
};

export interface DepartmentMetaData {
    about: object;
    levels: DepartmentLevel[];
    departmentLevelsIds: Array<string | undefined | null> | null;
    courses: DepartmentCourse[];
    departmentCoursesIds: Array<string | undefined | null> | null;
}

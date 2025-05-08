export type Course = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type CourseParams = {
    name: string;
    description?: string;
};

export type Department = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type DepartmentParams = {
    name: string;
    description?: string;
};

export type Division = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type DivisionParams = {
    name: string;
    description?: string;
};

export type Grade = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type GradeParams = {
    name: string;
    description?: string;
};

export type Level = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type LevelParams = {
    name: string;
    description?: string;
};

export type ModeOfStudy = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type ModeOfStudyParams = {
    name: string;
    description?: string;
};

export type Subject = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type SubjectParams = {
    name: string;
    description?: string;
};

export type InstitutionDepartment = {
    type?: string;
    id?: string | number;
    attributes: {
        departmentId: string | number;
        department: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type InstitutionDepartmentParams = {
    department_ids: Array<string | undefined | null> | null,
};

export type DepartmentLevel = {
    type?: string;
    id?: string | number;
    attributes: {
        departmentId: string | number;
        department: string;
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

export interface DepartmentMetaData {
    about: object;
    levels: DepartmentLevel[];
}

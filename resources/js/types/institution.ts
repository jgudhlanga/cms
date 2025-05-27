export type Course = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        position: string | number;
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
        position: string | number;
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
        position: string | number;
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
        position: string | number;
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
        position: string | number;
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
        position: string | number;
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
    id?: string | number | undefined;
    attributes: {
        departmentId: string | number;
        department: string;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    },
};

export type InstitutionDepartmentParams = {
    department_ids: Array<string | undefined | null> | null;
};

export type DepartmentLevelRequirementParams = {
    is_o_level_required: boolean;
    required_subjects_count: number | null;
    main_subjects_count: number | null;
    main_subject_ids: Array<string | undefined | null> | null;
    other_subjects_count: number | null;
    only_read_write_required: boolean;
    previous_level_id: string | null;
}

import { Subject } from '@/types/institution';

export type DepartmentLevel = {
    type?: string;
    id?: string | number;
    attributes: {
        institutionDepartmentId: string | number;
        levelId: string | number;
        level: string;
        levelPosition: number;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
    relationships?: {
        requirement?: DepartmentLevelRequirement;
    };
};

export type DepartmentLevelParams = {
    level_ids: Array<string | undefined | null> | null;
};

export type DepartmentCourse = {
    type?: string;
    id?: string | number;
    attributes: {
        institutionDepartmentId: string | number;
        courseId: string | number;
        course: string;
        showOnCurrentApplicationPeriod?: boolean;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
    relationships?: {
        departmentCourseLevels?: DepartmentCourseLevel[];
    };
};

export type DepartmentCourseLevel = {
    id?: string | number;
    departmentCourseId?: string | number;
    departmentLevelId?: string | number;
    level?: string;
};

export type DepartmentCourseParams = {
    course_ids: Array<string | undefined | null> | null;
};

export type DepartmentCourseUpdateParams = {
    department_level_ids?: Array<any> | null;
    show_on_current_application_period?: boolean;
};

export interface DepartmentMetaData {
    about: object;
    levels: DepartmentLevel[];
    departmentLevelsIds: Array<string | undefined | null> | null;
    courses: DepartmentCourse[];
    departmentCoursesIds: Array<string | undefined | null> | null;
}

export interface DepartmentCourseMetaData {
    courses: DepartmentCourse[];
    departmentCoursesIds: Array<string | undefined | null> | null;
}

export interface DepartmentLevelMetaData {
    levels: DepartmentCourse[];
    departmentLevelsIds: Array<string | undefined | null> | null;
}

export type DepartmentLevelRequirement = {
    type: string;
    id: string | number;
    attributes: {
        departmentLeveId: string | number;
        isOLevelRequired?: boolean;
        requiredSubjectsCount?: string | number | null;
        mainSubjectsCount?: string | number | null;
        mainSubjectIds?: Array<string | number | null>;
        otherSubjectsCount?: string | number | null;
        onlyReadWriteRequired?: boolean;
        requiredLevelId?: string | number | null;
        requiredLevel?: string | null;
    };
    relationships?: {
        subjects: Subject[];
    };
};

export type DepartmentLevelRequirementParams = {
    is_o_level_required?: boolean;
    required_subjects_count?: string | number;
    main_subjects_count?: string | number;
    main_subject_ids: any;
    other_subjects_count?: string | number;
    only_read_write_required?: boolean;
    required_level_id?: string | number | null;
};

export type DepartmentLevelCourse = {
    id: string | number;
    departmentCourseId: string | number;
    departmentLevelId: string | number;
    level: string;
    course: string;
};

export type DepartmentApplicationStep = {
    type: string;
    id: string | number;
    attributes: {
        institutionDepartmentId: string | number;
        workflowStepId: string | number;
        workflowStep: string;
        workflowStepDescription?: string;
        position: number;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string | null;
    };
    relationships?: {
        metadata?: DepartmentWorkflowStepMetadata,
    }
};

export type DepartmentWorkflowStepMetadata = {
    type: string;
    id: string;
    roleIds: Array<string | undefined | null>;
    staffIds: Array<string | undefined | null>;
    workflowActionIds: Array<string | undefined | null>;
    roles?:  Array<string | undefined | null>;
    staff?:  Array<string | undefined | null>;
    actions?: Array<{
        title: string;
        action: string;
    }>;
}

export type DepartmentApplicationStepParams = {
    workflow_step_ids: Array<string | undefined | null> | null;
};

export type DepartmentApplicationStepActionParams = {
    department_application_step_id: string | number | null;
    workflow_action_ids: Array<string | undefined | null>;
    role_ids: Array<string | undefined | null>;
    staff_ids: Array<string | undefined | null>;
};

export type ClassSizeEntry = {
    department_course_id: number;
    department_level_id: number;
    class_size: number | null;
};

export type DepartmentIntakeClassSizeParams = {
    intake_period_id: string | number | null;
    class_sizes: ClassSizeEntry[];
};
export type DepartmentIntakeClassSize = {
    type: string;
    id: string | number;
    attributes: {
        institutionDepartmentId: string | number;
        departmentCourseId: string | number;
        departmentLevelId: string | number;
        classSize: number | null;
        intakePeriodId: string | number | null;
    };
};

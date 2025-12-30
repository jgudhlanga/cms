export type Course = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        slug: string;
        position: string | number;
        description?: string;
        hasEnrolmentRequirements: boolean;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type CourseParams = {
    name: string;
    description?: string;
    has_enrolment_requirements?: boolean;
};

export type Department = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        slug: string;
        position: string | number;
        isAcademic?: boolean;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type DepartmentParams = {
    name: string;
    is_academic: boolean;
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
        allowedApplicationsPerLevel: string | number;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type LevelParams = {
    name: string;
    description?: string;
    allowed_applications_per_level?: string | number;
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
        departmentCode?: string;
        isAcademic: boolean | number;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type InstitutionDepartmentParams = {
    is_academic: boolean;
    department_ids: Array<string | undefined | null>;
};

export type IntakePeriod = {
    type?: string;
    id?: string;
    attributes: {
        name: string;
        startDate: string;
        endDate: string;
        isActive: boolean | number;
        description?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type IntakePeriodParams = {
    name: string;
    start_date: string;
    end_date: string;
    description?: string;
};
export type DocumentTemplate = {
    type: string;
    id: string;
    attributes: {
        name: string;
        documentTypeId: string | number;
        documentType: string;
        headerLine1: string;
        headerLine2: string;
        headerAddressLine1: string;
        headerAddressLine2: string;
        headerTelephone: string;
        headerEmail: string;
        headerWebsite: string;
        headerLogoOne: string;
        headerLogoOneUrl: string;
        headerLogo2: string;
        headerLogo2Url: string;
        body: string;
        logoOneUrl: string;
        createdAt: string;
        updatedAt: string;
        deletedAt: string;
    };
};
export type DocumentTemplateParams = {
    document_type_id: string | number | null;
    name: string;
    header_line_1: string;
    header_line_2: string;
    header_address_line_1: string;
    header_address_line_2: string;
    header_telephone: string;
    header_email: string;
    header_website: string;
    header_logo_1: string;
    header_logo_2: string;
    body: string;
};
export type FeeStructure = {
    type: string;
    id: string;
    attributes: {
        feeTypeId?: string | number;
        feeType?: string;
        levelId?: string | number;
        level?: string;
        modeOfStudyId?: string | number;
        modeOfStudy?: string;
        amount?: string;
        localFcaAmount?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type FeeStructureParams = {
    fee_type_id: string | number | null;
    level_id: string | number | null;
    mode_of_study_id: string | number | null;
    amount: string | null;
    local_fca_amount: string | null;
};

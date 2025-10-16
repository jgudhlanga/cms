import { DepartmentApplicationStep, DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { User } from '@/types/users';
import { SelectOption } from '@/types/utils';

export type Student = {
    type: string;
    id?: string | number;
    attributes: {
        userId: string | number;
        titleId?: string | number;
        title?: string;
        genderId?: string | number;
        gender?: string;
        maritalStatusId?: string | number;
        maritalStatus?: string;
        raceId?: string | number;
        race?: string;
        idTypeId: string | number;
        studentNumber: string;
        idType: string;
        idNumber?: string;
        passportNumber?: string;
        countryId?: string | number;
        country?: string;
        studentPermitNumber?: string;
        requiredExamSittingCount: string | number;
        dateOfBirth?: string;
        religionId?: string | number;
        religion?: string;
        denomination?: string;
        height?: string;
        weight?: string;
        disabilityStatus?: 'yes' | 'no' | 'prefer_not_to_say' | null;
    };
    relationships?: {
        user: User;
    };
};

export type PersonalDetailView = {
    title: string;
    firstname: string;
    middleName?: string | null;
    lastname: string;
    gender?: string;
    maritalStatus?: string;
    idTypeId?: string | number;
    idType?: string;
    dateOfBirth: string;
    idNumber?: string;
    passportNumber?: string;
    country?: string;
    studyPermitNumber?: string;
    race?: string;
    religion?: string;
    denomination?: string;
    height?: string;
    weight?: string;
    showAvatar?: boolean;
    avatarUrl?: string;
    disabilityStatus?: string;
};

export type ContactDetailView = {
    phoneNumber?: string;
    altPhoneNumber?: string;
    emailAddress?: string;
    altEmailAddress?: string;
    address1?: string;
    address2?: string;
    address3?: string;
    address4?: string;
};

export type NextOfKinDetailView = {
    name?: string;
    phoneNumber?: string;
    relationship?: string;
    address1?: string;
    address2?: string;
    address3?: string;
    address4?: string;
};

export type ProgramDetailView = {
    department?: string;
    level?: string;
    course?: string;
};

export type Sponsor = {
    type: string;
    id: string | number;
    attributes: {
        name: string;
        sponsorTypeId?: string | number;
        sponsorType?: string;
        phoneNumber?: string;
        email?: string;
        address1?: string;
        address2?: string;
        address3?: string;
        address4?: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};
export type SponsorParams = {
    name: string;
    sponsorType?: SelectOption | null;
    sponsor_type_id: string | number | null;
    phone_number?: string;
    email?: string;
    address_1?: string;
    address_2?: string;
    address_3?: string;
    address_4?: string;
};

export type StudentProgram = {
    type: string;
    id: string | number;
    attributes: {
        institutionDepartmentId: string | number;
        departmentLevelId: string | number;
        departmentCourseId: string | number;
        applicationTrackingNumber: string;
        registrationFeePaid: boolean;
        tuitionFeePaid: boolean;
        registrationFeeConfirmed: boolean;
        tuitionFeeConfirmed: boolean;
        modeOfStudyId: string | number;
        modeOfStudy: string;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
    relationships?: {
        student: Student;
        institutionDepartment?: InstitutionDepartment;
        departmentLevel?: DepartmentLevel;
        departmentCourse?: DepartmentCourse;
        departmentWorkflowStep?: DepartmentApplicationStep;
    };
};

export type AcademicRecord = {
    type: string;
    id: string | number;
    attributes: {
        studentId: string | number;
        academicLevelId: string | number;
        academicLevel: string;
        school: string;
        place: string;
        fromLevel?: string | number;
        toLevel?: string | number;
        fromYear?: string | number;
        toYear?: string | number;
        studentUniqueNumber: string;
        examBoard?: string;
        examMonth?: string;
        examYear?: string;
        examCenter?: string;
        examResults?: string;
    };
};

export type AcademicRecordParams = {
    academicLevel: SelectOption | null;
    academic_level_id: string | number | null;
    school: string;
    place: string;
    from_level?: string | number | null;
    to_level?: string | number | null;
    from_year?: string | number;
    to_year?: string | number;
    student_unique_number: string;
    exam_board?: string;
    exam_month?: string;
    exam_year?: string;
    exam_center?: string;
    exam_results?: string;
};

export type StudentPersonalDetailParams = {
    id_type_id: string | number | null;
    idType: SelectOption | null;
    id_number?: string | null;
    passport_number?: string | null;
    country?: SelectOption | null;
    country_id: string | number | null;
    date_of_birth: string | null;
    maritalStatus?: SelectOption | null;
    marital_status_id: string | number | null;
    title: SelectOption | null;
    title_id: string | number | null;
    gender: SelectOption | null;
    gender_id: string | number | null;
    race: SelectOption | null;
    race_id: string | number | null;
    religion: SelectOption | null;
    religion_id: string | number | null;
    denomination?: string | null;
    height?: string | null;
    weight?: string | null;
    disability_status: 'yes' | 'no' | 'prefer_not_to_say' | null;
};

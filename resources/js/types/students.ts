import { DepartmentApplicationStep, DepartmentCourse, DepartmentLevel } from '@/types/department-meta-data';
import { CourseSyllabus, InstitutionDepartment } from '@/types/institution';
import { User } from '@/types/users';
import { SelectOption } from '@/types/utils';
import type { Address, Contact } from '@/types/shared';
import type { NextOfKin } from '@/types/next-of-kin';

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
        idNumberValid?: boolean | null;
        suggestedIdNumber?: string | null;
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
        department?: string;
        level?: string;
        course?: string;
        modeOfStudy?: string;
        enrolmentStatus?: string;
        applicationStatus?: string;
        intakePeriod?: string;
        applicationTrackingNumber?: string;
        profileContext?: 'enrolled' | 'applicant' | null;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
    relationships?: {
        user: User;
        latestEnrolment: StudentEnrolment | null;
        mainContact: Contact,
        mainAddress: Address,
        nextOfKin: NextOfKin,
    };
};

export type StudentHeader = {
    studentId: string | number;
    studentName: string;
    avatarUrl?: any;
    studentNumber: string;
    level: string;
    course: string;
    modeOfStudy: string;
    enrolmentStatus: string;
    applicationStatus?: string;
    intakePeriod?: string;
    applicationTrackingNumber?: string;
    profileContext?: 'enrolled' | 'applicant' | null;
    department: string;
    academicCalendar: string;
    academicYearOption: string;
};

export type StudentProgrammeModule = {
    id?: number;
    code: string | null;
    name: string | null;
    durationInHours: number | null;
    grade: string | null;
    score: number | null;
    lecturer: string | null;
    type: string | null;
    assessment: string | null;
    courseWork?: StudentProgrammeModuleCourseWork | null;
};

export type StudentProgrammeModuleCourseWork = {
    assessments: Array<{
        assessmentTypeId: number;
        assessmentTypeName: string;
        markId: number | null;
        mark: number | null;
        remark: string | null;
    }>;
    aggregation: {
        components: Array<{
            assessmentTypeId: number;
            assessmentTypeName: string;
            rawMark: number | null;
            weightPercent: number;
            weightedMark: number | null;
        }>;
        courseWorkTotal60: number | null;
        isComplete: boolean;
        remark: string | null;
    };
};

export type StudentProgrammeSemester = {
    id: string;
    label: string | null;
    year: string | null;
    status: string | null;
    studentEnrolmentId?: number;
    module: StudentProgrammeModule[];
};

export type StudentProgramme = {
    id: string;
    level: string | null;
    course: string | null;
    courseCode: string | null;
    calendarYear: string | null;
    isActive?: boolean;
    semesters: StudentProgrammeSemester[];
};

export type StudentProgrammesApiResponse = {
    success: boolean;
    message: string;
    result: StudentProgramme[];
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

export type StudentApplication = {
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

export type StudentApplicationEdit = {
    institution_department_id: string | null;
    department_level_id: string | null;
    department_course_id: string | null;
    mode_of_study_id: string | null;
    department: SelectOption | null;
    level: SelectOption | null;
    course: SelectOption | null;
    modeOfStudy: SelectOption | null;
};


export type StudentFiltersState = {
    search?: string | null;
    name?: string | null;
    department?: number[] | null;
    level?: number[] | null;
    course?: number[] | null;
    mode_of_study?: number[] | null;
    gender?: 'male' | 'female';
    student_type?: 'direct' | 'apprentice';
    academic_year?: number[] | null;
    calendar_type?: string[] | null;
    with_trashed?: boolean | null;
};

export type StudentStatBreakdown = {
    id: number;
    name: string;
    count: number;
};

export type StudentTypeStatBreakdown = {
    id: 'direct' | 'apprentice';
    name: string;
    count: number;
};

export type StudentStats = {
    global: {
        total: number;
        male: number;
        female: number;
        byLevel: StudentStatBreakdown[];
        byModeOfStudy: StudentStatBreakdown[];
        byStudentType: StudentTypeStatBreakdown[];
    };
    filtered: {
        total: number;
    };
};

export type StudentEnrolment = {
    type: string;
    id: string | number;
    attributes: {
        instituionDepartmentId: string | number;
        studentId: string | number;
        studentApplicationId: string | number;
        departmentLevelId: string | number;
        departmentCourseId: string | number;
        modeOfStudyId: string | number;
        academicYearOptionId: string | number;
        academicCalendarId: string | number;
        studentEnrolmentStatusId: string | number;
        status: string;
        academicYearOption: string;
        academicCalendar: string;
    };
    relationships?: {
        details: {
            academicCalendarStudentEnrolmentId: string | number;
            academicCalendarClassId: string | number;
            classConfigId: string | number;
            syllabi: Array<CourseSyllabus>;
        };
    };
};

export type CourseWorkModuleStatusKey = 'in_progress' | 'not_graded' | 'graded';

export type CourseWorkModuleListItem = {
    id: string | number;
    code: string | null;
    name: string | null;
    statusKey: CourseWorkModuleStatusKey;
};

export type StudentPortalDashboardModule = {
    id: number;
    code: string | null;
    name: string | null;
    score: number | null;
    gradeDisplay: string;
    statusKey: CourseWorkModuleStatusKey;
    progressPercent: number;
};

export type StudentPortalDashboardActivity = {
    type: 'application' | 'financial' | 'course_work';
    message: string;
    severity: 'info' | 'warning' | 'success';
};

export type StudentPortalDashboardFinancial = {
    paidPercent: number;
    outstandingBalance: string;
    totalInvoiced: string;
    totalPayments: string;
};

export type StudentPortalDashboardTerm = {
    label: string;
    calendarYear: string;
    openingDate: string;
    closingDate: string | null;
};

export type StudentPortalDashboardNotice = {
    id: string | number;
    title: string;
    message: string;
    publishedAt: string | null;
};

export type StudentPortalCalendarType = 'term' | 'semester' | 'abma';

export type StudentPortalDashboardStats = {
    activeModuleCount: number;
    totalModuleHours: number;
    averageCourseWorkScore: number | null;
    oLevelSubjectCount: number;
    applicationCount: number;
    pendingApplicationCount: number;
    modules: StudentPortalDashboardModule[];
    activities: StudentPortalDashboardActivity[];
    notices: StudentPortalDashboardNotice[];
    calendarType: StudentPortalCalendarType;
    currentTerm: StudentPortalDashboardTerm | null;
    nextTerm: StudentPortalDashboardTerm | null;
    financial?: StudentPortalDashboardFinancial;
};
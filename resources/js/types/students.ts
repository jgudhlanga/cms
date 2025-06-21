export type PersonalDetailView = {
    title: string;
    firstname: string;
    middleName?: string | null;
    lastname: string;
    gender?: string;
    maritalStatus?: string;
    idType: string;
    dateOfBirth: string;
    idNumber?: string;
    passportNumber?: string;
    country?: string;
    studyPermitNumber?: string;
    religion?: string;
    denomination?: string;
    height?: string;
    weight?: string;
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

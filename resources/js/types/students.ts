import { SelectOption } from '@/types/utils';

export type Student = {
    userId: string | number;
    titleId?: string | number;
    title?: string;
    genderId?: string | number;
    gender?: string;
    maritalStatusId?: string | number;
    maritalStatus?: string;
    raceId?: string | number;
    race?: string;
    idType?: string;
    idNumber?: string;
    passportNumber?: string;
    countryId?: string | number;
    country?: string;
    studentPermitNumber?: string;
    dateOfBirth?: string;
    religionId?: string | number;
    religion?: string;
    denomination?: string;
    height?: string;
    weight?: string;
};

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
    race?: string;
    religion?: string;
    denomination?: string;
    height?: string;
    weight?: string;
    showAvatar?: boolean;
    avatarUrl?: string;
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

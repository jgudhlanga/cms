// Hostel Management System (HMS) types

import { Staff } from "./staff";

export type HostelWardenUser = {
    full_name?: string | null;
    first_name?: string | null;
    middle_name?: string | null;
    last_name?: string | null;
};

export type HostelWarden = {
    id: number | string;
    user?: HostelWardenUser | null;
};

export type Hostel = {
    type: string;
    id: number | string;
    attributes: {
        name: string;
        type: string;
        capacity: number;
        wardenId: number | string | null;
        roomsCount: number;
        floorCount: number;
        status: string;
        location: string;
        occupiedCount: number;
        vacantCount: number;
        maintenanceCount: number;
        description?: string;
        wardenName?: string | null;
        warden?: Staff | null;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type HostelFiltersState = {
    search?: string | null;
    type?: string | null;
    warden?: string | null;
    with_trashed?: boolean | null;
};

export type HostelRoom = {
    type: string;
    id: number | string;
    attributes: {
        hostelId: number | string;
        hostelName?: string | null;
        name: string;
        roomType: 'single' | 'double' | 'triple' | 'suite';
        capacity: number;
        occupancy: string;
        status: 'vacant' | 'occupied' | 'maintenance';
        maxOccupancy: number;
        floorNumber?: number | null;
        description?: string | null;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type HostelRoomFiltersState = {
    search?: string | null;
    hostel?: string | number | null;
    availableForApplication?: string | number | null;
    with_trashed?: boolean | null;
};

export type HostelRoomStats = {
    totalRooms: number;
    totalCapacity: number;
    totalMaxOccupancy: number;
    vacantCount: number;
};

export type HostelAllocationType = 'direct' | 'apprentice' | 'guest' | 'other';

export type HostelAllocationStatus = 'active' | 'checked-out' | 'closed' | 'pending';

export type HostelAllocation = {
    type: string;
    id: number | string;
    attributes: {
        allocationType: HostelAllocationType;
        allocationTypeLabel?: string | null;
        status: HostelAllocationStatus;
        statusLabel?: string | null;
        checkIn?: string | null;
        checkOut?: string | null;
        studentId?: number | string | null;
        studentNumber?: string | null;
        studentName?: string | null;
        gender?: string | null;
        course?: string | null;
        level?: string | null;
        hostelId?: number | string | null;
        hostelName?: string | null;
        roomId?: number | string | null;
        roomName?: string | null;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type HostelStudentFiltersState = {
    search?: string | null;
    gender?: number[] | null;
    hostel?: string | number | null;
    room?: string | null;
    type?: HostelAllocationType | null;
    status?: HostelAllocationStatus | null;
    with_trashed?: boolean | null;
};

export type HostelApplicationType = 'student' | 'guest';

export type HostelApplicationStatus = 'pending' | 'awaiting-payment' | 'approved' | 'declined';

export type HostelApplicationEligibilityRule = {
    key: string;
    passed: boolean;
    message: string;
};

export type HostelApplicationStudentLookup = {
    id: number;
    studentNumber?: string | null;
    name?: string | null;
    genderId?: number | null;
    gender?: string | null;
    phoneNumber?: string | null;
    physicalAddress?: string | null;
    emailAddress?: string | null;
    course?: string | null;
    level?: string | null;
    studentEnrolmentId?: number | null;
    nextOfKinName?: string | null;
    nextOfKinContact?: string | null;
};

export type HostelApplicationLookupSemester = {
    checkIn: string;
    checkOut: string;
    label: string;
};

export type HostelApplicationLookupRoomAvailability = {
    availableBeds: number;
    hostels: string[];
    roomCount: number;
};

export type HostelApplicationApprovalHostelOption = {
    id: number;
    name: string;
    availableBeds: number;
    isFull: boolean;
};

export type HostelApplicationApprovalRoomOption = {
    id: number;
    name: string;
    maxOccupancy: number;
    currentOccupancy: number;
    availableBeds: number;
    occupancyLabel: string;
};

export type HostelApplicationPaymentVerification = {
    addressOutsideCityCampusConfirmed?: boolean | null;
    fullTimeStudentConfirmed?: boolean | null;
    tuitionFeesPaidConfirmed?: boolean | null;
    accommodationFeesPaidConfirmed?: boolean | null;
};

export type HostelApplicationSidebarItem = {
    id: string | number;
    displayName: string;
    studentNumber?: string | null;
    status?: HostelApplicationStatus | null;
};

export type HostelApplicationPendingQueueResponse = {
    applications: HostelApplicationSidebarItem[];
};

export type HostelApplicationApprovalRoomsResponse = {
    rooms: HostelApplicationApprovalRoomOption[];
};

export type HostelApplicationPaymentVerificationKey =
    | 'addressOutsideCityCampusConfirmed'
    | 'fullTimeStudentConfirmed'
    | 'tuitionFeesPaidConfirmed'
    | 'accommodationFeesPaidConfirmed';

export type HostelApplicationApprovalOptionsResponse = {
    canApprove: boolean;
    blockers: string[];
    hostels: HostelApplicationApprovalHostelOption[];
    rooms: HostelApplicationApprovalRoomOption[];
    requiredPaymentVerification?: HostelApplicationPaymentVerificationKey[];
    allowsDirectAllocation?: boolean;
};

export type HostelApplicationStudentLookupResponse = {
    found: boolean;
    canSubmit?: boolean;
    message?: string;
    blockers?: string[];
    student?: HostelApplicationStudentLookup | null;
    semester?: HostelApplicationLookupSemester | null;
    roomAvailability?: HostelApplicationLookupRoomAvailability;
    eligibility?: HostelApplicationEligibilityRule[];
    eligibilityPassed?: boolean;
};

export type HostelApplication = {
    type: string;
    id: number | string;
    attributes: {
        applicationType: HostelApplicationType;
        applicationTypeLabel?: string | null;
        status: HostelApplicationStatus;
        statusLabel?: string | null;
        studentId?: number | string | null;
        studentEnrolmentId?: number | string | null;
        name?: string | null;
        genderId?: number | string | null;
        displayName?: string | null;
        studentName?: string | null;
        studentNumber?: string | null;
        gender?: string | null;
        course?: string | null;
        level?: string | null;
        departmentName?: string | null;
        calendarYear?: string | null;
        physicalAddress?: string | null;
        phoneNumber?: string | null;
        emailAddress?: string | null;
        nextOfKinName?: string | null;
        nextOfKinContact?: string | null;
        checkIn?: string | null;
        checkOut?: string | null;
        eligibilityResults?: HostelApplicationEligibilityRule[] | null;
        paymentVerification?: HostelApplicationPaymentVerification | null;
        declineReason?: string | null;
        createdAt?: string;
        updatedAt?: string;
        deletedAt?: string;
    };
};

export type HostelApplicationFiltersState = {
    search?: string | null;
    type?: HostelApplicationType | null;
    status?: HostelApplicationStatus | null;
    with_trashed?: boolean | null;
};

export type HmsSettings = {
    type: string;
    id: number | string;
    attributes: {
        requireFullTimeStudy: boolean;
        fullTimeModeName: string;
        requireTuitionPaid: boolean;
        requireAccommodationPaid: boolean;
        requireAddressOutsideCampus: boolean;
        campusCity: string;
        allowGuests: boolean;
        createdAt?: string;
        updatedAt?: string;
    };
};

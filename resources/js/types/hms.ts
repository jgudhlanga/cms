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

export type HostelWardenDepartmentContact = {
    id: number | string;
    name: string | null;
    code?: string | null;
    email?: string | null;
    phone?: string | null;
    location?: string | null;
};

export type HostelWardenProfile = {
    name: string | null;
    email?: string | null;
    phone?: string | null;
    employeeNumber?: string | null;
    departments: HostelWardenDepartmentContact[];
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
        floorNumber?: number | null;
        roomType?: string | null;
        roomStatus?: string | null;
        maxOccupancy?: number | null;
        currentOccupancy?: number | null;
        occupancyLabel?: string | null;
        amenities?: string[];
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

export type HostelApplicationEligibilitySeverity = 'success' | 'info' | 'warning';

export type HostelApplicationEligibilityRule = {
    key: string;
    passed: boolean;
    message: string;
    severity?: HostelApplicationEligibilitySeverity;
    modeOfStudy?: string | null;
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
    modeOfStudy?: string | null;
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
    canApply?: boolean;
    applyBlockers?: string[];
    message?: string;
    blockers?: string[];
    student?: HostelApplicationStudentLookup | null;
    semester?: HostelApplicationLookupSemester | null;
    roomAvailability?: HostelApplicationLookupRoomAvailability;
    eligibility?: HostelApplicationEligibilityRule[];
    eligibilityPassed?: boolean;
};

export type HostelAllocationRoommate = {
    id: number;
    studentId: number;
    name?: string | null;
    studentNumber?: string | null;
    course?: string | null;
    level?: string | null;
};

export type HostelAllocationRoommatesResponse = {
    roommates: HostelAllocationRoommate[];
};

export type StudentAccommodationFeePayment = {
    date?: string | null;
    amount: string;
    description?: string | null;
};

export type StudentAccommodationFeesResponse = {
    calendarYear?: string | null;
    intakeLabel?: string | null;
    total: string;
    paid: string;
    due: string;
    isFullyPaid: boolean;
    paymentHistory: StudentAccommodationFeePayment[];
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

export type HostelQueryCategory =
    | 'maintenance'
    | 'plumbing'
    | 'electrical'
    | 'cleanliness'
    | 'security'
    | 'other';

export type HostelQueryPriority = 'low' | 'medium' | 'high';

export type HostelQueryStatus = 'open' | 'in-progress' | 'resolved' | 'closed';

export type HostelQuery = {
    type: string;
    id: number | string;
    attributes: {
        studentId?: number | string;
        category: HostelQueryCategory;
        categoryLabel?: string;
        subject: string;
        description: string;
        priority: HostelQueryPriority;
        priorityLabel?: string;
        status: HostelQueryStatus;
        statusLabel?: string;
        studentName?: string | null;
        studentNumber?: string | null;
        createdAt?: string;
    };
};

export type HostelLeaveStatus = 'pending' | 'approved' | 'declined' | 'cancelled';

export type HostelLeave = {
    type: string;
    id: number | string;
    attributes: {
        studentId?: number | string;
        leaveType: string;
        fromDate: string;
        toDate: string;
        reason?: string | null;
        status: HostelLeaveStatus;
        statusLabel?: string;
        studentName?: string | null;
        studentNumber?: string | null;
        reviewedByName?: string | null;
        createdAt?: string;
    };
};

export type HostelNoticeType = 'general' | 'event' | 'urgent' | 'rule';

export type HostelNoticeStatus = 'pending' | 'published' | 'cancelled' | 'expired';

export type HostelNoticeAudienceFloor = {
    hostelId: number;
    floorNumber: number;
};

export type HostelNotice = {
    type: string;
    id: number | string;
    attributes: {
        title: string;
        content: string;
        noticeType: HostelNoticeType;
        noticeTypeLabel?: string;
        status: HostelNoticeStatus;
        statusLabel?: string;
        isUrgent?: boolean;
        postedByName?: string | null;
        publishedAt?: string | null;
        expiresAt?: string | null;
        audienceHostelIds?: number[];
        audienceFloors?: HostelNoticeAudienceFloor[];
        audienceStudentIds?: number[];
        createdAt?: string;
    };
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

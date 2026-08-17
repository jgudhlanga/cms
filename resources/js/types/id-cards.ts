export type IdCardRequestStatus =
    | 'awaiting_payment'
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'printed'
    | 'issued';

export type IdCardRequestReason = 'new' | 'lost' | 'damaged' | 'renewal';

export type IdCardRequestAttributes = {
    studentId?: number;
    status: IdCardRequestStatus;
    statusLabel: string;
    reason: IdCardRequestReason;
    reasonLabel: string;
    notes?: string | null;
    rejectionReason?: string | null;
    serialNumber?: string | null;
    photoThumbUrl?: string | null;
    studentName?: string | null;
    studentNumber?: string | null;
    programme?: string | null;
    reviewedByName?: string | null;
    reviewedAt?: string | null;
    printedAt?: string | null;
    issuedAt?: string | null;
    createdAt?: string | null;
};

export type IdCardRequest = {
    type: string;
    id: string | number;
    attributes: IdCardRequestAttributes;
};

export type IdCardRequestFiltersState = {
    search?: string;
    status?: string;
    reason?: string;
};

export type StudentIdCardFace = {
    studentName: string;
    studentNumber: string;
    department: string;
    level: string;
    course: string;
    mode: string;
    sdp: string;
    residence: string;
    expiryDate: string;
    nationalId: string;
    returnName: string;
    returnAddress: string;
    returnPhone: string;
    institutionName?: string | null;
    website?: string | null;
    logoUrl?: string | null;
    signatureUrl?: string | null;
};

export type StudentIdCardSettings = {
    institutionName: string;
    website: string | null;
    returnName: string;
    returnAddress: string;
    returnPhone: string | null;
    logoUrl: string;
    signatureUrl: string | null;
};

export type IdCardFilterOption = {
    value: string;
    label: string;
};

export type StudentIdCardRequestPayload = {
    id: number;
    status: IdCardRequestStatus;
    statusLabel: string;
    reason: IdCardRequestReason;
    reasonLabel: string;
    notes: string | null;
    rejectionReason: string | null;
    serialNumber: string | null;
    photoUrl: string | null;
    photoThumbUrl: string | null;
    requiresFee: boolean;
    feeAmount: number;
    feeLedgerId: number | null;
    reviewedAt: string | null;
    printedAt: string | null;
    issuedAt: string | null;
    createdAt: string | null;
    student: {
        id: number | null;
        name: string | null;
    } & StudentIdCardFace;
    reviewerName: string | null;
    printerName: string | null;
    issuerName: string | null;
};

export type AccountPurgeArchiveStatus = 'active' | 'restored' | 'flushed';

export type AccountPurgeArchivePurgeType = 'student_account' | 'user_account';

export interface AccountPurgeArchiveAttributes {
    purgeType: AccountPurgeArchivePurgeType;
    purgeTypeLabel: string;
    status: AccountPurgeArchiveStatus;
    statusLabel: string;
    name: string | null;
    email: string | null;
    studentNumber: string | null;
    purgeReason: string | null;
    purgedByName: string | null;
    purgedAt: string | null;
    flushAfter: string | null;
    flushedAt: string | null;
    restoredAt: string | null;
    daysUntilFlush: number | null;
    canRestore: boolean;
    canFlush: boolean;
    originalUserId: number | null;
    originalStudentId: number | null;
    archiveRetentionDays: number;
}

export interface AccountPurgeArchive {
    type: 'account-purge-archive';
    id: number;
    attributes: AccountPurgeArchiveAttributes;
}

export interface MaintenanceArchivesFiltersState {
    search?: string;
    purgeType?: AccountPurgeArchivePurgeType | 'all';
    status?: AccountPurgeArchiveStatus | 'all';
}

export interface AccountPurgeArchiveRestoreResult {
    userId: number | null;
    studentId: number | null;
    studentProfileUrl: string | null;
    userProfileUrl: string | null;
}

export interface AccountPurgeArchiveRestoreResponse {
    message: string;
    data: AccountPurgeArchiveRestoreResult;
}

export interface AccountPurgeArchiveDialogTarget {
    id: number;
    name: string;
    email: string | null;
    purgeType: AccountPurgeArchivePurgeType;
    purgeTypeLabel: string;
}

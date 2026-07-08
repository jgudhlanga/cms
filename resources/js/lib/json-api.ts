import type { DataListProps, PaginationLink } from '@/types/data-pagination';
import type { Enrolment } from '@/types/enrolments';
import type { CourseWorkStudentTree, CourseWorkTree } from '@/types/course-work';
import type { StudentPortalDashboardStats } from '@/types/students';
import type {
    HostelAmenity,
    HostelAllocation,
    HostelApplication,
    HostelApplicationFiltersState,
    HostelApplicationStudentLookupResponse,
    HostelFiltersState,
    HostelRoom,
    HostelRoomFiltersState,
    HostelRoomStats,
    HostelLeave,
    HostelNotice,
    HostelQuery,
    HostelStudentFiltersState,
    HmsSettings,
} from '@/types/hms';

export const JSON_API_ACCEPT = 'application/vnd.api+json';

type JsonApiResource = {
    type: string;
    id: string;
    attributes: Record<string, unknown>;
};

type JsonApiPageMeta = {
    currentPage?: number;
    lastPage?: number;
    perPage?: number;
    total?: number;
    from?: number | null;
    to?: number | null;
    current_page?: number;
    last_page?: number;
    per_page?: number;
};

type JsonApiCollectionDocument = {
    data?: JsonApiResource[];
    links?: Record<string, string | null>;
    meta?: {
        page?: JsonApiPageMeta;
    };
};

type JsonApiHostelRoomStatsMeta = {
    totalRooms?: number;
    totalCapacity?: number;
    totalMaxOccupancy?: number;
    vacantCount?: number;
};

type JsonApiMetaDocument = {
    meta?: JsonApiHostelRoomStatsMeta & Partial<StudentPortalDashboardStats>;
};

export function jsonApiRequestConfig(): { headers: { Accept: string } } {
    return { headers: { Accept: JSON_API_ACCEPT } };
}

export function jsonApiWriteConfig(): { headers: { Accept: string; 'Content-Type': string } } {
    return {
        headers: {
            Accept: JSON_API_ACCEPT,
            'Content-Type': JSON_API_ACCEPT,
        },
    };
}

export function toHostelJsonApiFilters(filters: HostelFiltersState): Record<string, string> {
    const out: Record<string, string> = {};

    if (filters.search) {
        out.search = String(filters.search);
    }
    if (filters.type) {
        const type = String(filters.type).toLowerCase();
        if (type === 'male' || type === 'female' || type === 'mixed') {
            out.type = type;
        }
    }
    if (filters.warden) {
        out.warden = String(filters.warden);
    }
    if (filters.with_trashed) {
        out.trashed = '1';
    }

    return out;
}

export function toHostelRoomJsonApiFilters(filters: HostelRoomFiltersState): Record<string, string> {
    const out: Record<string, string> = {};

    if (filters.search) {
        out.search = String(filters.search);
    }
    if (filters.hostel !== undefined && filters.hostel !== null && filters.hostel !== '') {
        out.hostel = String(filters.hostel);
    }
    if (filters.availableForApplication !== undefined && filters.availableForApplication !== null && filters.availableForApplication !== '') {
        out.availableForApplication = String(filters.availableForApplication);
    }
    if (filters.with_trashed) {
        out.trashed = '1';
    }

    return out;
}

export function toHostelAllocationJsonApiFilters(filters: HostelStudentFiltersState): Record<string, string> {
    const out: Record<string, string> = {};

    if (filters.search) {
        out.search = String(filters.search);
    }
    if (filters.gender?.length) {
        out.gender = filters.gender.map(String).join(',');
    }
    if (filters.hostel !== undefined && filters.hostel !== null && filters.hostel !== '') {
        out.hostel = String(filters.hostel);
    }
    if (filters.room) {
        out.room = String(filters.room);
    }
    if (filters.type) {
        out.type = String(filters.type);
    }
    if (filters.status) {
        out.status = String(filters.status);
    }
    if (filters.with_trashed) {
        out.trashed = '1';
    }

    return out;
}

export function buildJsonApiIndexParams(
    filters: Record<string, string>,
    page?: { number?: number; size?: number },
): Record<string, unknown> {
    const params: Record<string, unknown> = {};

    if (Object.keys(filters).length > 0) {
        params.filter = filters;
    }

    if (page?.number !== undefined || page?.size !== undefined) {
        params.page = {
            ...(page.number !== undefined ? { number: page.number } : {}),
            ...(page.size !== undefined ? { size: page.size } : {}),
        };
    }

    return params;
}

export type JsonApiTableQueryOptions = {
    search?: string;
    trashed?: string | number;
    pageNumber?: number;
    pageSize?: number;
};

export function appendJsonApiTableQueryToUrl(url: string, options: JsonApiTableQueryOptions): string {
    const base = typeof window !== 'undefined' ? window.location.origin : 'https://localhost';
    const parsed = new URL(url, base);

    if (options.search) {
        parsed.searchParams.set('filter[search]', options.search);
    } else {
        parsed.searchParams.delete('filter[search]');
    }

    if (options.trashed !== undefined && String(options.trashed) !== '0') {
        parsed.searchParams.set('filter[trashed]', '1');
    } else {
        parsed.searchParams.delete('filter[trashed]');
    }

    if (options.pageNumber !== undefined) {
        parsed.searchParams.set('page[number]', String(options.pageNumber));
    }

    if (options.pageSize !== undefined) {
        parsed.searchParams.set('page[size]', String(options.pageSize));
    }

    return `${parsed.pathname}${parsed.search}`;
}

export function mergeJsonApiFiltersIntoRequestPath(
    url: string,
    filters: Record<string, string>,
): string {
    const base = typeof window !== 'undefined' ? window.location.origin : 'https://localhost';
    const parsed = new URL(url, base);

    for (const [key, val] of Object.entries(filters)) {
        if (val !== '') {
            parsed.searchParams.set(`filter[${key}]`, val);
        }
    }

    return `${parsed.pathname}${parsed.search}`;
}

export function buildPaginationPageLinks(currentPage: number, lastPage: number): PaginationLink[] {
    if (lastPage < 1) {
        return [];
    }

    return Array.from({ length: lastPage }, (_, index) => {
        const pageNumber = index + 1;

        return {
            url: null,
            label: pageNumber,
            active: pageNumber === currentPage,
        };
    });
}

function mapJsonApiPageMeta(page?: JsonApiPageMeta) {
    const currentPage = page?.currentPage ?? page?.current_page ?? 1;
    const lastPage = page?.lastPage ?? page?.last_page ?? 1;
    const perPage = page?.perPage ?? page?.per_page ?? 15;
    const total = page?.total ?? 0;

    return {
        current_page: currentPage,
        last_page: lastPage,
        per_page: perPage,
        total,
        from: page?.from ?? (total > 0 ? 1 : 0),
        to: page?.to ?? total,
        path: null,
        links: buildPaginationPageLinks(currentPage, lastPage),
    };
}

function mapJsonApiLinks(links?: Record<string, string | null>) {
    return {
        first: links?.first ?? null,
        last: links?.last ?? null,
        prev: links?.prev ?? null,
        next: links?.next ?? null,
    };
}

export function parseJsonApiHostels(document: JsonApiCollectionDocument): DataListProps<Hostel> {
    const rows: Hostel[] = (document.data ?? []).map((resource) => ({
        type: resource.type,
        id: resource.id,
        attributes: resource.attributes as Hostel['attributes'],
    }));

    return {
        data: rows,
        meta: mapJsonApiPageMeta(document.meta?.page),
        links: mapJsonApiLinks(document.links),
    };
}

export function parseJsonApiHostelAmenities(document: JsonApiCollectionDocument): DataListProps<HostelAmenity> {
    const rows: HostelAmenity[] = (document.data ?? []).map((resource) => ({
        type: resource.type,
        id: resource.id,
        attributes: resource.attributes as HostelAmenity['attributes'],
    }));

    return {
        data: rows,
        meta: mapJsonApiPageMeta(document.meta?.page),
        links: mapJsonApiLinks(document.links),
    };
}

export function parseJsonApiHostelRooms(document: JsonApiCollectionDocument): DataListProps<HostelRoom> {
    const rows: HostelRoom[] = (document.data ?? []).map((resource) => ({
        type: resource.type,
        id: resource.id,
        attributes: resource.attributes as HostelRoom['attributes'],
    }));

    return {
        data: rows,
        meta: mapJsonApiPageMeta(document.meta?.page),
        links: mapJsonApiLinks(document.links),
    };
}

export function parseJsonApiHostelRoomStats(document: JsonApiMetaDocument): HostelRoomStats {
    const meta = document.meta ?? {};

    return {
        totalRooms: meta.totalRooms ?? 0,
        totalCapacity: meta.totalCapacity ?? 0,
        totalMaxOccupancy: meta.totalMaxOccupancy ?? 0,
        vacantCount: meta.vacantCount ?? 0,
    };
}

export function parseJsonApiStudentPortalDashboardStats(document: JsonApiMetaDocument): StudentPortalDashboardStats {
    const meta = document.meta ?? {};

    return {
        activeModuleCount: meta.activeModuleCount ?? 0,
        totalModuleHours: meta.totalModuleHours ?? 0,
        averageCourseWorkScore: meta.averageCourseWorkScore ?? null,
        oLevelSubjectCount: meta.oLevelSubjectCount ?? 0,
        applicationCount: meta.applicationCount ?? 0,
        pendingApplicationCount: meta.pendingApplicationCount ?? 0,
        modules: meta.modules ?? [],
        activities: meta.activities ?? [],
        notices: meta.notices ?? [],
        calendarType: meta.calendarType ?? 'semester',
        currentTerm: meta.currentTerm ?? null,
        nextTerm: meta.nextTerm ?? null,
        financial: meta.financial,
    };
}

export function toHostelApplicationJsonApiFilters(filters: HostelApplicationFiltersState): Record<string, string> {
    const out: Record<string, string> = {};

    if (filters.search) {
        out.search = String(filters.search);
    }
    if (filters.type) {
        out.type = String(filters.type);
    }
    if (filters.status) {
        out.status = String(filters.status);
    }
    if (filters.with_trashed) {
        out.trashed = '1';
    }

    return out;
}

export function parseJsonApiHostelApplications(document: JsonApiCollectionDocument): DataListProps<HostelApplication> {
    const rows: HostelApplication[] = (document.data ?? []).map((resource) => ({
        type: resource.type,
        id: resource.id,
        attributes: resource.attributes as HostelApplication['attributes'],
    }));

    return {
        data: rows,
        meta: mapJsonApiPageMeta(document.meta?.page),
        links: mapJsonApiLinks(document.links),
    };
}

export function parseJsonApiHostelApplication(document: {
    data?: JsonApiResource | JsonApiResource[];
}): HostelApplication | undefined {
    const raw = document.data;
    const resource = Array.isArray(raw) ? raw[0] : raw;

    if (!resource) {
        return undefined;
    }

    return {
        type: resource.type,
        id: resource.id,
        attributes: resource.attributes as HostelApplication['attributes'],
    };
}

export function parseJsonApiHmsSettings(document: JsonApiCollectionDocument): HmsSettings | undefined {
    const resource = document.data?.[0];
    if (!resource) {
        return undefined;
    }

    return {
        type: resource.type,
        id: resource.id,
        attributes: resource.attributes as HmsSettings['attributes'],
    };
}

export function parseJsonApiHmsSetting(document: { data?: JsonApiResource }): HmsSettings | undefined {
    const resource = document.data;
    if (!resource) {
        return undefined;
    }

    return {
        type: resource.type,
        id: resource.id,
        attributes: resource.attributes as HmsSettings['attributes'],
    };
}
export type JsonApiStudentApplicationAttributes = {
    studentId?: number;
    department?: string;
    level?: string;
    course?: string;
    intakePeriodId?: number;
    intakePeriod?: string;
    intakePeriodCalendarYear?: string;
    intakePeriodStartDate?: string;
    applicationTrackingNumber?: string;
    workflowStep?: string;
    createdAt?: string;
    updatedAt?: string;
};

export function mapJsonApiStudentApplicationToEnrolment(resource: JsonApiResource): Enrolment {
    const attributes = resource.attributes as JsonApiStudentApplicationAttributes;

    return {
        type: 'enrolments',
        id: resource.id,
        attributes: {
            studentId: attributes.studentId ?? '',
            studentName: '',
            modeOfStudyId: '',
            modeOfStudy: '',
            phoneNumber: '',
            email: '',
            institutionDepartmentId: '',
            departmentLevelId: '',
            departmentCourseId: '',
            department: attributes.department ?? '',
            level: attributes.level ?? '',
            levelId: '',
            intakePeriod: attributes.intakePeriod ?? '',
            intakePeriodId: attributes.intakePeriodId ?? '',
            intakePeriodCalendarYear: attributes.intakePeriodCalendarYear,
            intakePeriodStartDate: attributes.intakePeriodStartDate,
            allowedApplicationsPerLevel: 0,
            hasEnrolmentRequirements: false,
            course: attributes.course ?? '',
            applicationTrackingNumber: attributes.applicationTrackingNumber ?? '',
            requiredExamSittingCount: 0,
            registrationFeeConfirmed: false,
            tuitionFeeConfirmed: false,
            requiredLevelCompleted: false,
            readWriteAcknowledged: false,
            createdAt: attributes.createdAt ?? '',
            updatedAt: attributes.updatedAt ?? '',
            deletedAt: '',
        },
        relationships: {
            oLevelResults: [],
            departmentWorkflowStep: {
                type: 'department-application-step',
                id: '',
                attributes: {
                    institutionDepartmentId: '',
                    workflowStepId: '',
                    workflowStep: attributes.workflowStep ?? '',
                    slug: '',
                    workflowStepDescription: '',
                    position: 0,
                    createdAt: '',
                    updatedAt: '',
                    deletedAt: '',
                },
            },
        },
    };
}

export function parseJsonApiStudentApplications(document: JsonApiCollectionDocument): Enrolment[] {
    return (document.data ?? []).map(mapJsonApiStudentApplicationToEnrolment);
}

export const COURSE_WORK_MARK_RESOURCE_TYPE = 'course-work-marks';

export function parseJsonApiCourseWorkTree(document: { meta?: Record<string, unknown> }): CourseWorkTree | null {
    const meta = document.meta;
    if (!meta) {
        return null;
    }

    const hasClassScope = typeof meta.academicCalendarClassId === 'number';
    const hasClassConfigScope = typeof meta.classConfigId === 'number';

    if (!hasClassScope && !hasClassConfigScope) {
        return null;
    }

    return meta as unknown as CourseWorkTree;
}

export function parseJsonApiCourseWorkStudentTree(document: { meta?: Record<string, unknown> }): CourseWorkStudentTree | null {
    const meta = document.meta;
    if (!meta || typeof meta.academicCalendarClassId !== 'number' || typeof meta.studentEnrolmentId !== 'number') {
        return null;
    }

    return meta as unknown as CourseWorkStudentTree;
}

export function courseWorkStudentFilterParams(
    academicCalendarClassId: number,
    studentEnrolmentId: number,
): { filter: { academicCalendarClass: string; studentEnrolment: string } } {
    return {
        filter: {
            academicCalendarClass: String(academicCalendarClassId),
            studentEnrolment: String(studentEnrolmentId),
        },
    };
}

export function buildCourseWorkMarkPayload(
    attributes: Record<string, unknown>,
    id?: string | number,
): { data: { type: string; id?: string; attributes: Record<string, unknown> } } {
    return {
        data: {
            type: COURSE_WORK_MARK_RESOURCE_TYPE,
            ...(id !== undefined ? { id: String(id) } : {}),
            attributes,
        },
    };
}

export function courseWorkClassFilterParams(academicCalendarClassId: number): { filter: { academicCalendarClass: string } } {
    return {
        filter: {
            academicCalendarClass: String(academicCalendarClassId),
        },
    };
}

export function courseWorkClassConfigFilterParams(classConfigId: number): { filter: { classConfig: string } } {
    return {
        filter: {
            classConfig: String(classConfigId),
        },
    };
}

export function parseJsonApiHostelAllocations(document: JsonApiCollectionDocument): DataListProps<HostelAllocation> {
    const rows: HostelAllocation[] = (document.data ?? []).map((resource) => ({
        type: resource.type,
        id: resource.id,
        attributes: resource.attributes as HostelAllocation['attributes'],
    }));

    return {
        data: rows,
        meta: mapJsonApiPageMeta(document.meta?.page),
        links: mapJsonApiLinks(document.links),
    };
}

function parseJsonApiCollection<T extends { type: string; id: number | string; attributes: unknown }>(
    document: JsonApiCollectionDocument,
): DataListProps<T> {
    const rows = (document.data ?? []).map((resource) => ({
        type: resource.type,
        id: resource.id,
        attributes: resource.attributes,
    })) as T[];

    return {
        data: rows,
        meta: mapJsonApiPageMeta(document.meta?.page),
        links: mapJsonApiLinks(document.links),
    };
}

export function parseJsonApiHostelQueries(document: JsonApiCollectionDocument): DataListProps<HostelQuery> {
    return parseJsonApiCollection<HostelQuery>(document);
}

export function parseJsonApiHostelLeaves(document: JsonApiCollectionDocument): DataListProps<HostelLeave> {
    return parseJsonApiCollection<HostelLeave>(document);
}

export function parseJsonApiHostelNotices(document: JsonApiCollectionDocument): DataListProps<HostelNotice> {
    return parseJsonApiCollection<HostelNotice>(document);
}

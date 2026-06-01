import { errorAlert, successAlert } from '@/lib/alerts';
import {
    buildJsonApiIndexParams,
    jsonApiRequestConfig,
    jsonApiWriteConfig,
    parseJsonApiHostelLeaves,
    parseJsonApiHostelNotices,
    parseJsonApiHostelQueries,
} from '@/lib/json-api';
import HttpService from '@/services/http.service';
import type { HostelLeave, HostelNotice, HostelQuery } from '@/types/hms';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export function useStudentAccommodationServices(studentId: () => string) {
    const queries = ref<HostelQuery[]>([]);
    const leaves = ref<HostelLeave[]>([]);
    const notices = ref<HostelNotice[]>([]);
    const isQueriesLoading = ref(false);
    const isLeavesLoading = ref(false);
    const isNoticesLoading = ref(false);

    const fetchQueries = async () => {
        const id = studentId();
        if (!id) {
            return;
        }

        isQueriesLoading.value = true;
        try {
            const document = await HttpService.get(route('v1.json.hms.hostel-queries.index'), {
                ...jsonApiRequestConfig(),
                params: buildJsonApiIndexParams({ student: id }, { size: 50 }),
            });
            queries.value = parseJsonApiHostelQueries(document).data;
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('hms.query', 2) }));
            queries.value = [];
        } finally {
            isQueriesLoading.value = false;
        }
    };

    const fetchLeaves = async () => {
        const id = studentId();
        if (!id) {
            return;
        }

        isLeavesLoading.value = true;
        try {
            const document = await HttpService.get(route('v1.json.hms.hostel-leaves.index'), {
                ...jsonApiRequestConfig(),
                params: buildJsonApiIndexParams({ student: id }, { size: 50 }),
            });
            leaves.value = parseJsonApiHostelLeaves(document).data;
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('hms.leave', 2) }));
            leaves.value = [];
        } finally {
            isLeavesLoading.value = false;
        }
    };

    const fetchNotices = async () => {
        const id = studentId();
        if (!id) {
            return;
        }

        isNoticesLoading.value = true;
        try {
            const document = await HttpService.get(route('v1.json.hms.hostel-notices.index'), {
                ...jsonApiRequestConfig(),
                params: buildJsonApiIndexParams({ student: id }, { size: 50 }),
            });
            notices.value = parseJsonApiHostelNotices(document).data;
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('hms.notice', 2) }));
            notices.value = [];
        } finally {
            isNoticesLoading.value = false;
        }
    };

    const createQuery = async (payload: {
        category: string;
        subject: string;
        description: string;
        priority?: string;
    }): Promise<boolean> => {
        const id = studentId();
        if (!id) {
            return false;
        }

        try {
            await HttpService.post(
                route('v1.json.hms.hostel-queries.store'),
                {
                    data: {
                        type: 'hostel-queries',
                        attributes: {
                            studentId: Number(id),
                            category: payload.category,
                            subject: payload.subject,
                            description: payload.description,
                            priority: payload.priority ?? 'medium',
                        },
                    },
                },
                jsonApiWriteConfig(),
            );
            successAlert(trans('hms.query_saved'));
            await fetchQueries();
            return true;
        } catch {
            errorAlert(trans('hms.query_save_failed'));
            return false;
        }
    };

    const createLeave = async (payload: {
        leaveType: string;
        fromDate: string;
        toDate: string;
        reason?: string;
    }): Promise<boolean> => {
        const id = studentId();
        if (!id) {
            return false;
        }

        try {
            await HttpService.post(
                route('v1.json.hms.hostel-leaves.store'),
                {
                    data: {
                        type: 'hostel-leaves',
                        attributes: {
                            studentId: Number(id),
                            leaveType: payload.leaveType,
                            fromDate: payload.fromDate,
                            toDate: payload.toDate,
                            reason: payload.reason ?? '',
                        },
                    },
                },
                jsonApiWriteConfig(),
            );
            successAlert(trans('hms.leave_saved'));
            await fetchLeaves();
            return true;
        } catch {
            errorAlert(trans('hms.leave_save_failed'));
            return false;
        }
    };

    const createNotice = async (payload: {
        title: string;
        content: string;
        noticeType: string;
        status?: string;
        isUrgent?: boolean;
        audienceHostelIds?: number[];
        audienceFloors?: { hostelId: number; floorNumber: number }[];
        audienceStudentIds?: number[];
    }): Promise<boolean> => {
        try {
            await HttpService.post(
                route('v1.json.hms.hostel-notices.store'),
                {
                    data: {
                        type: 'hostel-notices',
                        attributes: {
                            title: payload.title,
                            content: payload.content,
                            noticeType: payload.noticeType,
                            status: payload.status ?? 'pending',
                            isUrgent: payload.isUrgent ?? false,
                            audienceHostelIds: payload.audienceHostelIds ?? [],
                            audienceFloors: payload.audienceFloors ?? [],
                            audienceStudentIds: payload.audienceStudentIds ?? [],
                        },
                    },
                },
                jsonApiWriteConfig(),
            );
            successAlert(trans('hms.notice_saved'));
            await fetchNotices();
            return true;
        } catch {
            errorAlert(trans('hms.notice_save_failed'));
            return false;
        }
    };

    const loadAll = async () => {
        await Promise.all([fetchQueries(), fetchLeaves(), fetchNotices()]);
    };

    return {
        queries,
        leaves,
        notices,
        isQueriesLoading,
        isLeavesLoading,
        isNoticesLoading,
        fetchQueries,
        fetchLeaves,
        fetchNotices,
        createQuery,
        createLeave,
        createNotice,
        loadAll,
    };
}

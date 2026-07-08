import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { AcademicCalendar } from '@/types/academic-calendar';
import { AssessmentCalendar, AssessmentType } from '@/types/institution';
import type { Link } from '@/types/ui';
import { SelectOption } from '@/types/utils';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { z } from 'zod';

const calendarTypeValues = ['semester', 'term', 'abma'] as const;

export type AssessmentCalendarLimits = {
    semester: number;
    term: number;
    abma: number;
};

export type AssessmentCalendarFormSchemaContext = {
    academicCalendars: AcademicCalendar[];
    existingRecords: AssessmentCalendar[];
    editingId?: string | null;
    limits: AssessmentCalendarLimits;
};

export const normalizeAssessmentCalendarRecords = (
    records: AssessmentCalendar[] | { data?: AssessmentCalendar[] },
): AssessmentCalendar[] => {
    if (Array.isArray(records)) {
        return records;
    }

    return records?.data ?? [];
};

export const getAssessmentCalendarLimit = (type: string, limits: AssessmentCalendarLimits): number => {
    if (type === 'term') {
        return limits.term;
    }

    if (type === 'abma') {
        return limits.abma;
    }

    return limits.semester;
};

export const countExistingForType = (
    existingRecords: AssessmentCalendar[],
    type: string,
    editingId?: string | null,
): number => {
    return existingRecords.filter(
        (record) => record.attributes?.type === type && String(record.id) !== String(editingId ?? ''),
    ).length;
};

export const filterAcademicCalendarsForType = (
    academicCalendars: AcademicCalendar[],
    type: string,
    existingRecords: AssessmentCalendar[],
    editingId?: string | null,
): AcademicCalendar[] => {
    const usedIds = new Set(
        existingRecords
            .filter((record) => String(record.id) !== String(editingId ?? ''))
            .map((record) => String(record.attributes?.academicCalendarId)),
    );

    return academicCalendars.filter(
        (calendar) => calendar.attributes?.type === type && !usedIds.has(String(calendar.id)),
    );
};

export const isAllAssessmentCalendarLimitsReached = (
    existingRecords: AssessmentCalendar[],
    limits: AssessmentCalendarLimits,
): boolean => {
    return calendarTypeValues.every(
        (type) => countExistingForType(existingRecords, type) >= getAssessmentCalendarLimit(type, limits),
    );
};

export const buildCalendarTypeOptions = (): SelectOption[] =>
    calendarTypeValues.map((value) => ({
        value,
        label: trans_choice(`academic_calendar.${value}`, 1),
    }));

export const defaultCalendarTypeOption = (): SelectOption => ({
    value: 'semester',
    label: trans_choice('academic_calendar.semester', 1),
});

export const resolveCalendarTypeOption = (type?: string | null): SelectOption => {
    const match = buildCalendarTypeOptions().find((option) => option.value === type);

    return match ?? defaultCalendarTypeOption();
};

export const resolveAcademicCalendarOption = (
    academicCalendars: AcademicCalendar[],
    academicCalendarId?: string | number | null,
): SelectOption | null => {
    if (!academicCalendarId) {
        return null;
    }

    const id = String(academicCalendarId);
    const calendar = academicCalendars.find((item) => String(item.id) === id);

    if (!calendar) {
        return null;
    }

    return {
        value: id,
        label: calendar.attributes?.name ?? id,
    };
};

export const defaultAssessmentCalendarDates = (calendar: AcademicCalendar) => ({
    start_date: calendar.attributes.openingDate,
    end_date: calendar.attributes.closingDate,
});

export const findAcademicCalendarById = (
    academicCalendars: AcademicCalendar[],
    academicCalendarId?: string | number | null,
): AcademicCalendar | undefined => {
    if (!academicCalendarId) {
        return undefined;
    }

    return academicCalendars.find((calendar) => String(calendar.id) === String(academicCalendarId));
};

export const useAssessmentCalendars = (assessmentType: AssessmentType) => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const assessmentTypeId = String(assessmentType.id);

    const createAssessmentCalendarColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;

        return [
            {
                header: trans_choice('trans.type', 1),
                accessorKey: 'attributes.type',
                cell: ({ row }: { row: { original: AssessmentCalendar } }) =>
                    trans_choice(`academic_calendar.${row.original.attributes?.type}`, 1),
            },
            {
                header: trans_choice('academic_calendar.academic_calendar', 1),
                accessorKey: 'attributes.academicCalendarName',
            },
            {
                header: trans_choice('trans.assessment_type', 1),
                accessorKey: 'attributes.assessmentTypeName',
                cell: () => assessmentType.attributes?.name ?? '',
            },
            { header: trans('trans.start_date'), accessorKey: 'attributes.startDate' },
            { header: trans('trans.end_date'), accessorKey: 'attributes.endDate' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: AssessmentCalendar } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.assessment_calendar', 1);

                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:assessment-calendar'], row.original) },
                        {
                            key: 'archive',
                            action: () =>
                                onDelete(
                                    can['delete:assessment-calendar'],
                                    route('assessment-calendars.destroy', {
                                        assessment_type: assessmentTypeId,
                                        calendar: id,
                                    }),
                                    name,
                                ),
                        },
                        {
                            key: 'restore',
                            action: () =>
                                onRestore(
                                    can['restore:assessment-calendar'],
                                    route('assessment-calendars.restore', {
                                        assessment_type: assessmentTypeId,
                                        calendar: id,
                                    }),
                                    name,
                                ),
                        },
                        {
                            key: 'delete',
                            action: () =>
                                onForceDelete(
                                    can['forceDelete:assessment-calendar'],
                                    route('assessment-calendars.force-delete', {
                                        assessment_type: assessmentTypeId,
                                        calendar: id,
                                    }),
                                    name,
                                ),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [
        { transChoiceKey: 'institution', href: route('institution.index') },
        { transKey: 'institution_setup', href: route('institution.setup') },
        { transChoiceKey: 'assessment_type', href: route('assessment-types.index') },
        { title: assessmentType.attributes?.name ?? '' },
        { transChoiceKey: 'assessment_calendar' },
    ];

    const formSchema = (context: AssessmentCalendarFormSchemaContext) =>
        z
            .object({
                academic_calendar_id: z.string().nonempty(
                    trans('trans.enter_required_field', { field: trans_choice('academic_calendar.academic_calendar', 1) }),
                ),
                start_date: z
                    .union([z.string(), z.date()])
                    .transform((val) => (typeof val === 'string' ? val : val.toISOString().split('T')[0]))
                    .refine((val) => !isNaN(Date.parse(val)), {
                        message: trans('trans.date_must_be_valid', { field: trans('trans.start_date') }),
                    }),
                end_date: z
                    .union([z.string(), z.date()])
                    .transform((val) => (typeof val === 'string' ? val : val.toISOString().split('T')[0]))
                    .refine((val) => !isNaN(Date.parse(val)), {
                        message: trans('trans.date_must_be_valid', { field: trans('trans.end_date') }),
                    }),
                type: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.type', 1) })),
            })
            .refine((data) => new Date(data.end_date) >= new Date(data.start_date), {
                message: trans('trans.end_date_start_date_validation'),
                path: ['end_date'],
            })
            .superRefine((data, ctx) => {
                const selectedAcademicCalendar = context.academicCalendars.find(
                    (calendar) => String(calendar.id) === String(data.academic_calendar_id),
                );

                if (!selectedAcademicCalendar) {
                    return;
                }

                if (selectedAcademicCalendar.attributes?.type !== data.type) {
                    ctx.addIssue({
                        code: z.ZodIssueCode.custom,
                        message: trans('trans.assessment_calendar_type_mismatch'),
                        path: ['type'],
                    });
                }

                const openingDate = selectedAcademicCalendar.attributes?.openingDate;
                const closingDate = selectedAcademicCalendar.attributes?.closingDate;

                if (openingDate && closingDate) {
                    const startDate = new Date(data.start_date);
                    const endDate = new Date(data.end_date);
                    const opening = new Date(openingDate);
                    const closing = new Date(closingDate);

                    if (startDate < opening || startDate > closing) {
                        ctx.addIssue({
                            code: z.ZodIssueCode.custom,
                            message: trans('trans.assessment_calendar_date_out_of_range', {
                                field: trans('trans.start_date'),
                                opening: openingDate,
                                closing: closingDate,
                            }),
                            path: ['start_date'],
                        });
                    }

                    if (endDate < opening || endDate > closing) {
                        ctx.addIssue({
                            code: z.ZodIssueCode.custom,
                            message: trans('trans.assessment_calendar_date_out_of_range', {
                                field: trans('trans.end_date'),
                                opening: openingDate,
                                closing: closingDate,
                            }),
                            path: ['end_date'],
                        });
                    }
                }

                const duplicateExists = context.existingRecords.some(
                    (record) =>
                        String(record.attributes?.academicCalendarId) === String(data.academic_calendar_id) &&
                        String(record.id) !== String(context.editingId ?? ''),
                );

                if (duplicateExists) {
                    ctx.addIssue({
                        code: z.ZodIssueCode.custom,
                        message: trans('trans.assessment_calendar_academic_calendar_already_used'),
                        path: ['academic_calendar_id'],
                    });
                }

                const existingCount = countExistingForType(context.existingRecords, data.type, context.editingId);
                const maxAllowed = getAssessmentCalendarLimit(data.type, context.limits);

                if (existingCount >= maxAllowed) {
                    ctx.addIssue({
                        code: z.ZodIssueCode.custom,
                        message: trans('trans.assessment_calendar_year_limit_reached', {
                            type: trans_choice(`academic_calendar.${data.type}`, 1),
                            max: String(maxAllowed),
                            year: String(new Date().getFullYear()),
                        }),
                        path: ['type'],
                    });
                }
            });

    const saveAssessmentCalendar = (form: InertiaForm<any>, assessmentCalendar?: AssessmentCalendar) => {
        const success = trans('trans.item_saved', { item: trans_choice('trans.assessment_calendar', 1) });
        const error = trans('trans.item_save_failure', { item: trans_choice('trans.assessment_calendar', 1) });

        if (assessmentCalendar) {
            const id = getIdParams(assessmentCalendar.id?.toString() ?? '');
            form.put(
                route('assessment-calendars.update', {
                    assessment_type: assessmentTypeId,
                    calendar: id,
                }),
                buildFormOptions(form, success, error, APP_MODULE_KEYS.assessment_type_calendars),
            );
        } else {
            form.post(
                route('assessment-calendars.store', { assessment_type: assessmentTypeId }),
                buildFormOptions(form, success, error, APP_MODULE_KEYS.assessment_type_calendars),
            );
        }
    };

    const onOpenModal = (can: boolean, assessmentCalendar?: AssessmentCalendar) => {
        if (!can) {
            return forbiddenAlert();
        }

        openModal({ name: APP_MODULE_KEYS.assessment_type_calendars, edit: assessmentCalendar });
    };

    return {
        createAssessmentCalendarColumns,
        breadcrumbs,
        saveAssessmentCalendar,
        onOpenModal,
        formSchema,
    };
};

export type AssessmentCalendarPageProps = {
    academicCalendars: AcademicCalendar[];
    existingAssessmentCalendars: AssessmentCalendar[] | { data?: AssessmentCalendar[] };
    assessmentCalendarLimits: AssessmentCalendarLimits;
};

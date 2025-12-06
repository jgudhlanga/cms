import { useDataTables } from '@/composables/core/useDataTables';
import { closeModal, errorAlert, forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { AcademicCalendar } from '@/types/academic-calendar';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { useDropdowns } from '@/composables/core/useDropdowns';

export const useAcademicCalendars = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const createTableColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.type', 1), accessorKey: 'attributes.type' },
            { header: trans_choice('trans.opening_date', 1), accessorKey: 'attributes.openingDate' },
            { header: trans_choice('trans.closing_date', 1), accessorKey: 'attributes.closingDate' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: AcademicCalendar } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.module', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:academic-calendars'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:academic-calendars'], route('academic-calendars.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:academic-calendars'], route('academic-calendars.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:academic-calendars'], route('academic-calendars.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [
        {
            transChoiceKey: 'institution',
            href: route('institution.index'),
        },
        { transKey: 'institution_setup', href: route('institution.setup') },
        { transChoiceKey: 'academic_calendar' },
    ];

    const onOpenModal = (can: boolean, edit?: AcademicCalendar) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.academic_calendars, edit: edit });
    };

    const storeAcademicCalendar = (form: InertiaForm<any>) => {
        form.post(route('academic-calendars.store'), {
            onSuccess: () => {
                successAlert('Academic calendar successfully created');
                closeModal(APP_MODULE_KEYS.academic_calendars);
            },
            onError: (errors: any) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert('An unexpected error happened, academic calendar could not be created');
                }
            },
        });
    };

    const updateAcademicCalendar = (form: InertiaForm<any>, academicCalendar: AcademicCalendar) => {
        form.put(route('academic-calendars.update', String(academicCalendar?.id ?? '')), {
            onSuccess: () => {
                successAlert('Academic calendar successfully updated');
                closeModal(APP_MODULE_KEYS.academic_calendars);
            },
            onError: (errors: any) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert('An unexpected error happened, academic calendar could not be updated');
                }
            },
        });
    };
    const saveAcademicCalendar = (form: InertiaForm<any>, academicCalendar?: AcademicCalendar) => {
        try {
            if (academicCalendar) {
                updateAcademicCalendar(form, academicCalendar);
            } else {
                storeAcademicCalendar(form);
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const isLoading = ref(false);
    const academicCalendars = ref<AcademicCalendar[]>([]);

    const listAcademicCalendars = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: route('v1.academic-calendars.index'), search, transChoiceKey: 'trans.academic_calendar' });
        isLoading.value = false;
        academicCalendars.value = data.value;
    };

    return {
        createTableColumns,
        breadcrumbs,
        onOpenModal,
        saveAcademicCalendar,
        isLoading,
        academicCalendars,
        listAcademicCalendars,
    };
};

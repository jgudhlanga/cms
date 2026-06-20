import { useDropdowns } from '@/composables/core/useDropdowns';
import { closeModal, errorAlert, forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import HttpService from '@/services/http.service';
import { useDepartmentMetaStore } from '@/store/institution/useDepartmentMetaStore';
import { AcademicCalendar } from '@/types/academic-calendar';
import type { Link } from '@/types/ui';
import type { SelectOption } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { ref } from 'vue';

export const useAcademicCalendars = () => {
    const breadcrumbs: Array<Link> = [
        {
            transChoiceKey: 'institution',
            href: route('institution.index'),
        },
        { transKey: 'institution_setup', href: route('institution.setup') },
        { transChoiceKey: 'academic_calendar.academic_calendar' },
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

    const saveAcademicCalendar = (form: InertiaForm<any>, academicCalendar?: AcademicCalendar | null) => {
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

    const listAcademicYearOptions = async (): Promise<SelectOption[]> => {
        const body = await HttpService.get(route('v1.academic-calendars.options'));
        const rows = (body?.data ?? []) as Array<{ academicYear: string }>;
        return rows.map((row) => ({
            value: row.academicYear,
            label: row.academicYear,
        }));
    };

    const storePerClassSizeConfig = (form: InertiaForm<any>, institutionDepartmentId: string, academicCalendarId: string, onSuccess?: () => void) => {
        form.post(
            route('academic-calendars.classes-config.per-class-size.store', {
                institution_department: institutionDepartmentId,
                academic_calendar: academicCalendarId,
            }),
            {
                onSuccess: () => {
                    successAlert('Academic calendar successfully config successfully saved');
                    closeModal(APP_MODULE_KEYS.student_per_class);
                    useDepartmentMetaStore().bumpAcademicClassConfigsRefresh();
                    onSuccess?.();
                },
                onError: (errors: any) => {
                    if (Object.keys(errors).length) {
                        const allErrors = Object.values(errors).join('\n');
                        errorAlert(allErrors);
                    } else {
                        errorAlert('An unexpected error happened, academic calendar could not be updated');
                    }
                },
            },
        );
    };

    return {
        breadcrumbs,
        onOpenModal,
        saveAcademicCalendar,
        isLoading,
        academicCalendars,
        listAcademicCalendars,
        listAcademicYearOptions,
        storePerClassSizeConfig,
    };
};

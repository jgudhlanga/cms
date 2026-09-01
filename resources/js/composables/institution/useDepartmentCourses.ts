import { IconButton } from '@/components/core/button';
import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/enums/icons';
import { closeModal, errorAlert, forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { toggleFormLoader } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { useCreateApplicationFormStore } from '@/store/portal/useCreateApplicationFormStore';
import { useUpdateProgramFormStore } from '@/store/portal/useUpdateProgramFormStore';
import { Auth } from '@/types';
import { CourseRequirement, DepartmentCourse, DepartmentCourseLevel, DepartmentCourseMetaData } from '@/types/department-meta-data';
import { AcademicOLevelResult, Enrolment } from '@/types/enrolments';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { storeToRefs } from 'pinia';
import { h, ref } from 'vue';
import { z } from 'zod';

export const useDepartmentCourses = (isEditingProgram?: boolean) => {
    const { moreActionButton, textLink, checkStatusIcon, onEdit } = useDataTables();
    const { props } = usePage();
    const { can } = props?.auth as Auth;
    const { navigateTo, formatDate, isItTrue } = useUtils();
    const store = isEditingProgram ? useUpdateProgramFormStore() : useCreateApplicationFormStore();
    const createDepartmentCourseColumns = () => {
        return [
            {
                header: trans_choice('trans.course', 1),
                accessorKey: 'course',
                cell: ({ row }: { row: { original: DepartmentCourse } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return hasAbility('create:department-metadata')
                        ? textLink(route('department-courses.show', id), row.original.attributes?.course)
                        : row.original.attributes?.course;
                },
            },
            {
                header: trans_choice('trans.level', 2),
                accessorKey: 'levels',
                cell: ({ row }: { row: { original: DepartmentCourse } }) => {
                    const levels = (row.original.relationships?.departmentCourseLevels ?? [])
                        .map((item: DepartmentCourseLevel) => item.level)
                        .filter(Boolean);

                    if (levels.length === 0) {
                        return h('span', { class: 'text-muted-foreground' }, '—');
                    }

                    return h(
                        'div',
                        { class: 'flex flex-wrap gap-1' },
                        levels.map((level) =>
                            h(
                                'span',
                                {
                                    key: level,
                                    class: 'inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium whitespace-nowrap text-primary',
                                },
                                level,
                            ),
                        ),
                    );
                },
            },
            {
                header: trans_choice('trans.mode_of_study', 2),
                accessorKey: 'modes',
                enableSorting: false,
                cell: ({ row }: { row: { original: DepartmentCourse } }) => {
                    const modes = (row.original.relationships?.modes ?? []).map((mode) => mode.attributes?.name).filter(Boolean);

                    return h('div', { class: 'flex flex-wrap items-center gap-1.5' }, [
                        modes.length === 0
                            ? h('span', { class: 'text-muted-foreground text-xs' }, trans('trans.not_set'))
                            : h(
                                  'div',
                                  { class: 'flex flex-wrap gap-1' },
                                  modes.map((mode) =>
                                      h(
                                          'span',
                                          {
                                              key: mode,
                                              class: 'inline-flex items-center rounded-full bg-violet-500/10 px-2 py-0.5 text-[11px] font-medium whitespace-nowrap text-violet-600',
                                          },
                                          mode,
                                      ),
                                  ),
                              ),
                        h('span', { title: trans_choice('general.mode', 2) }, [
                            h(IconButton, {
                                icon: IconName.settings,
                                tone: 'header-primary',
                                iconSize: '14',
                                onClick: () => navigateTo(route('department-courses.modes', { department_course: String(row.original.id) })),
                            }),
                        ]),
                    ]);
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: DepartmentCourse } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'edit',
                            action: () => onEdit(can['update:department-metadata'], route('department-courses.show', id)),
                        },
                    ]);
                },
            },
        ];
    };

    const createCourseLevelEnrolmentColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'studentName',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return textLink(route('department-courses.show', id), row.original.attributes?.studentName);
                },
            },
            {
                header: trans('trans.tracking_number'),
                accessorKey: 'applicationTrackingNumber',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    return row.original?.attributes?.applicationTrackingNumber;
                },
            },
            {
                header: trans('trans.application_date'),
                accessorKey: 'applicationDate',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    return formatDate(row.original?.attributes?.createdAt);
                },
            },
            {
                header: trans_choice('trans.grade', 2),
                accessorKey: 'grades',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    return row.original.relationships?.oLevelResults?.map((item: AcademicOLevelResult) => item?.attributes?.grade)?.join(', ');
                },
            },
            {
                header: trans_choice('trans.score', 1),
                accessorKey: 'score',
                enableSorting: false,
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    const score = row.original.relationships?.oLevelResults?.reduce((acc: number, result) => {
                        return acc + (result.attributes?.gradePosition ? parseFloat(result.attributes.gradePosition as string) : 0);
                    }, 0);
                    return score;
                },
            },
            {
                header: trans_choice('trans.status', 1),
                accessorKey: 'status',
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    return row.original.relationships?.workflowStep?.attributes?.name ?? '---';
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Enrolment } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'edit',
                            action: () => onEdit(can['update:department-metadata'], route('department-courses.show', id)),
                        },
                    ]);
                },
            },
        ];
    };

    const syncDepartmentCourses = (institutionDepartmentId: string, form: InertiaForm<any>) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.course', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.course', 2) });
            form.post(route('department-courses.sync', institutionDepartmentId), {
                preserveScroll: true,
                onSuccess: () => {
                    successAlert(success);
                    closeModal(APP_MODULE_KEYS.department_courses);
                },
                onError: (errors) => errorAlert(errors?.course_ids ?? error),
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const updateDepartmentCourses = (departmentCourseId: string, form: InertiaForm<any>, institutionDepartmentId: string) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.course', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.course', 2) });
            form.post(route('department-courses.update', departmentCourseId), {
                onStart: () => toggleFormLoader(true),
                onFinish: () => {
                    form.reset();
                    toggleFormLoader(false);
                },
                onSuccess: () => {
                    successAlert(success);
                    navigateTo(route('institution-departments.show', getIdParams(institutionDepartmentId)));
                },
                onError: () => {
                    errorAlert(error);
                },
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const openDepartmentCoursesModal = (departmentCourses: Array<string | undefined | null> | null) => {
        if (!can['department-setup:courses']) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.department_courses, edit: departmentCourses });
    };

    const isLoading = ref(false);
    const departmentCoursesMetaData = ref<DepartmentCourseMetaData | null>(null);

    const loadDepartmentCoursesMetaData = async (institutionDepartmentId: string) => {
        try {
            isLoading.value = true;
            departmentCoursesMetaData.value = await HttpService.get(route('v1.department-metadata.courses', institutionDepartmentId));
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.course', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    const courserRequirementsFormSchema = (isOLevelRequired: boolean) =>
        z.object({
            required_subjects_count: isOLevelRequired
                ? z
                      .string()
                      .nonempty(trans('trans.enter_required_field', { field: trans('trans.required_subjects_count') }))
                      .refine((val: string) => !isNaN(Number(val)), {
                          message: trans('trans.field_must_be_number', { field: trans('trans.required_subjects_count') }),
                      })
                : z.string().optional(),
            main_subjects_count: isOLevelRequired
                ? z
                      .string()
                      .nonempty(trans('trans.enter_required_field', { field: trans('trans.main_subjects_count') }))
                      .refine((val: string) => !isNaN(Number(val)), {
                          message: trans('trans.field_must_be_number', { field: trans('trans.main_subjects_count') }),
                      })
                : z.string().optional(),
            other_subjects_count: isOLevelRequired
                ? z
                      .string()
                      .nonempty(trans('trans.enter_required_field', { field: trans('trans.other_subjects_count') }))
                      .refine((val: string) => !isNaN(Number(val)), {
                          message: trans('trans.field_must_be_number', { field: trans('trans.other_subjects_count') }),
                      })
                : z.string().optional(),
        });

    const storeCourseRequirements = (departmentCourseId: string, form: InertiaForm<any>, institutionDepartmentId: string) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.course', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.course', 2) });
            form.post(route('department-courses.store-requirements', departmentCourseId), {
                onStart: () => toggleFormLoader(true),
                onFinish: () => {
                    form.reset();
                    toggleFormLoader(false);
                },
                onSuccess: () => {
                    successAlert(success);
                    navigateTo(route('institution-departments.show', getIdParams(institutionDepartmentId)));
                },
                onError: () => {
                    errorAlert(error);
                },
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const {
        courseRequirements: requirements,
        o_level_subject_ids,
        required_level_completed,
        read_write_acknowledged,
    } = storeToRefs(store);

    const courseRequirements = ref<CourseRequirement | null>(requirements?.value ?? null);

    const listCourseRequirements = async (departmentLevelId: string, departmentCourseId: string) => {
        if (Number(departmentLevelId) > 0 && Number(departmentCourseId) > 0) {
            isLoading.value = true;
            courseRequirements.value = await HttpService.get(
                `api/v1/institution-departments/${departmentLevelId}/courses/${departmentCourseId}/requirements`,
            );

            requirements!.value = courseRequirements.value;
            o_level_subject_ids!.value = null;
            required_level_completed!.value = null;
            read_write_acknowledged!.value = null;
            isLoading.value = false;
        }
    };

    const saveCourseLevelModes = (departmentCourseId: string, form: InertiaForm<any>, institutionDepartmentId: string) => {
        try {
            const name = trans_choice('general.mode', 2);
            const success = trans('trans.item_saved', { item: name });
            const error = trans('trans.item_save_failure', { item: name });
            form.post(route('department-courses.modes.store', departmentCourseId), {
                onStart: () => toggleFormLoader(true),
                onFinish: () => {
                    form.reset();
                    toggleFormLoader(false);
                },
                onSuccess: () => {
                    successAlert(success);
                    navigateTo(route('institution-departments.show', getIdParams(institutionDepartmentId)));
                },
                onError: () => {
                    errorAlert(error);
                },
            });
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return {
        createDepartmentCourseColumns,
        openDepartmentCoursesModal,
        syncDepartmentCourses,
        updateDepartmentCourses,
        isLoading,
        departmentCoursesMetaData,
        loadDepartmentCoursesMetaData,
        createCourseLevelEnrolmentColumns,
        courserRequirementsFormSchema,
        storeCourseRequirements,
        listCourseRequirements,
        courseRequirements,
        saveCourseLevelModes,
    };
};

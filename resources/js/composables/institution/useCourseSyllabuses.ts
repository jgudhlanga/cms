import { useDataTables } from '@/composables/core/useDataTables';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import HttpService from '@/services/http.service';
import { CourseSyllabus } from '@/types/institution';
import { ApiFilterResponse } from '@/types/data-pagination';
import { InertiaForm, router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { z } from 'zod';

export const useCourseSyllabuses = () => {
    const { actionButton, onView } = useDataTables();
    const isLoading = ref(false);
    const courseSyllabuses = ref<ApiFilterResponse | null>(null);

    const formSchema = () =>
        z.object({
            institution_department_id: z.number().min(1, trans('trans.select_valid_field', { field: trans_choice('trans.department', 1) })),
            department_level_course_id: z.number().min(1, trans('trans.enter_required_field', { field: trans_choice('trans.course', 1) })),
            title: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.title', 1) })),
            code: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.code', 1) })),
            implementation_year: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.year', 1) })),
            status: z.enum(['active', 'terminated']),
        });

    const listCourseSyllabuses = async (institutionDepartmentId: string) => {
        try {
            isLoading.value = true;
            courseSyllabuses.value = await HttpService.get(route('department-course-syllabuses.index', institutionDepartmentId));
        } finally {
            isLoading.value = false;
        }
    };

    const saveCourseSyllabus = (form: InertiaForm<any>, courseSyllabusId?: string) => {
        const success = trans('trans.item_saved', { item: 'course syllabus' });
        const error = trans('trans.item_save_failure', { item: 'course syllabus' });
        const opts = buildFormOptions(form, success, error);
        const hasFile = form.syllabus_document instanceof File;

        if (courseSyllabusId) {
            if (hasFile) {
                form
                    .transform((data) => ({ ...data, _method: 'put' }))
                    .post(route('department-course-syllabuses.update', courseSyllabusId), { ...opts, forceFormData: true });
            } else {
                form.put(route('department-course-syllabuses.update', courseSyllabusId), opts);
            }
            return;
        }

        form.post(route('department-course-syllabuses.store'), hasFile ? { ...opts, forceFormData: true } : opts);
    };

    const createCourseSyllabusColumns = (institutionDepartmentId: string | number) => {
        return [
            { header: trans_choice('trans.level', 1), accessorKey: 'attributes.level' },
            { header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
            { header: trans_choice('trans.code', 1), accessorKey: 'attributes.code' },
            { header: trans('syllabus.implementation_year'), accessorKey: 'attributes.implementationYear' },
            { header: trans('syllabus.status'), accessorKey: 'attributes.status' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: CourseSyllabus } }) => {
                    return actionButton({
                        title: trans('trans.view'),
                        onClick: () =>
                            onView(
                                hasAbility('viewAny:department-metadata'),
                                route('department-course-syllabuses.show', {
                                    institution_department: institutionDepartmentId,
                                    course_syllabus: row.original.id,
                                }),
                            ),
                    });
                },
            },
        ];
    };

    const onCreateCourseSyllabus = (institutionDepartmentId: string | number) => {
        router.get(route('department-course-syllabuses.create', { institution_department: institutionDepartmentId }));
    };

    const onEditCourseSyllabus = (institutionDepartmentId: string | number, courseSyllabusId: string | number) => {
        router.get(
            route('department-course-syllabuses.edit', { institution_department: institutionDepartmentId, course_syllabus: courseSyllabusId }),
        );
    };

    return {
        isLoading,
        courseSyllabuses,
        formSchema,
        listCourseSyllabuses,
        saveCourseSyllabus,
        createCourseSyllabusColumns,
        onCreateCourseSyllabus,
        onEditCourseSyllabus,
    };
};

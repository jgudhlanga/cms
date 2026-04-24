import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import HttpService from '@/services/http.service';
import { ApiFilterResponse } from '@/types/data-pagination';
import { SyllabusCourseModule } from '@/types/institution';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';
import { z } from 'zod';
import { ColorVariant } from '@/enums/colors';

export const useSyllabusCourseModules = () => {
    const { actionButton } = useDataTables();
    const isLoading = ref(false);
    const syllabusCourseModules = ref<ApiFilterResponse | null>(null);

    const formSchema = () =>
        z.object({
            course_syllabus_id: z.number().min(1, trans('trans.select_valid_field', { field: trans_choice('syllabus.course_syllabus', 1) })),
            title: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.title', 1) })),
            code: z.string().nonempty(trans('trans.enter_required_field', { field: trans_choice('trans.code', 1) })),
            duration_in_hours: z.number().int().positive().nullable(),
            nql_level: z.number().int().positive().nullable(),
            prerequisite_module_ids: z.array(z.number().int().positive()).default([]),
            shared: z.boolean(),
        });

    const listSyllabusCourseModules = async (institutionDepartmentId: string, courseSyllabusId: string, url?: string) => {
        const endpoint = url ?? route('syllabus-course-modules.index', { institution_department: institutionDepartmentId, course_syllabus: courseSyllabusId });

        try {
            isLoading.value = true;
            syllabusCourseModules.value = await HttpService.get(endpoint);
        } finally {
            isLoading.value = false;
        }
    };

    const saveSyllabusCourseModule = (form: InertiaForm<any>, moduleId?: string) => {
        const success = trans('trans.item_saved', { item: trans_choice('syllabus.module', 1) });
        const error = trans('trans.item_save_failure', { item: trans_choice('syllabus.module', 1) });
        const opts = buildFormOptions(form, success, error, APP_MODULE_KEYS.syllabus_course_modules);

        if (moduleId) {
            form.put(route('syllabus-course-modules.update', moduleId), opts);
            return;
        }

        form.post(route('syllabus-course-modules.store'), opts);
    };

    const createSyllabusCourseModuleColumns = (onEdit: (module: SyllabusCourseModule) => void, canUpdate: boolean) => [
        { header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
        { header: trans_choice('trans.code', 1), accessorKey: 'attributes.code' },
        { header: trans('syllabus.duration_in_hours'), accessorKey: 'attributes.durationInHours', meta: { align: 'center' } },
        { header: trans('syllabus.nql_level'), accessorKey: 'attributes.nqlLevel', meta: { align: 'center' } },
        {
            header: trans('syllabus.shared'),
            accessorKey: 'attributes.shared',
            meta: { align: 'center' },
            cell: ({ row }: { row: { original: SyllabusCourseModule } }) => (row.original.attributes.shared ? trans('trans.yes') : trans('trans.no')),
        },
        {
            header: trans_choice('trans.action', 2),
            accessorKey: 'actions',
            enableSorting: false,
            meta: { align: 'right' },
            cell: ({ row }: { row: { original: SyllabusCourseModule } }) =>
                h('div', { class: 'flex items-center justify-end gap-2' }, [
                    actionButton({
                        title: trans('trans.edit'),
                        variant: ColorVariant.primary_outline,
                        onClick: () => onOpenModal(canUpdate, { courseSyllabusId: row.original.attributes.courseSyllabusId }, row.original),
                    }),
                ]),
        },
    ];

    const onOpenModal = (can: boolean, parent: { courseSyllabusId: string | number }, module?: SyllabusCourseModule) => {
        if (!can) {
            return forbiddenAlert();
        }

        openModal({ name: APP_MODULE_KEYS.syllabus_course_modules, edit: module, parent });
    };

    return {
        isLoading,
        syllabusCourseModules,
        formSchema,
        listSyllabusCourseModules,
        saveSyllabusCourseModule,
        createSyllabusCourseModuleColumns,
        onOpenModal,
    };
};

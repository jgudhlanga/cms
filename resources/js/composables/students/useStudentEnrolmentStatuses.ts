import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { StudentEnrolmentStatus } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useStudentEnrolmentStatuses = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const studentEnrolmentStatuses = ref<StudentEnrolmentStatus[]>([]);
    const getName = () => trans_choice('students.enrolment_status', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });

    const createColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: StudentEnrolmentStatus } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');

                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:settings'), row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:settings'), route('student-enrolment-statuses.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:settings'), route('student-enrolment-statuses.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () =>
                                onForceDelete(hasAbility('forceDelete:settings'), route('student-enrolment-statuses.force-delete', id), getName()),
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
        {
            transKey: 'institution_setup',
            href: route('institution.setup'),
        },
        { transChoiceKey: 'students.enrolment_status' },
    ];

    const save = (form: InertiaForm<any>, status?: StudentEnrolmentStatus) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            if (status) {
                const id = getIdParams(status.id?.toString() ?? '');
                form.put(
                    route('student-enrolment-statuses.update', id),
                    buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.student_enrolment_statuses),
                );
            } else {
                form.post(
                    route('student-enrolment-statuses.store'),
                    buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.student_enrolment_statuses),
                );
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const onOpenModal = (can: boolean, status?: StudentEnrolmentStatus) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.student_enrolment_statuses, edit: status });
    };

    const list = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({
            url: 'api/v1/student-enrolment-statuses?page_size=all',
            search,
            transChoiceKey: 'students.enrolment_status',
        });
        isLoading.value = false;
        studentEnrolmentStatuses.value = data.value;
    };

    return {
        createColumns,
        breadcrumbs,
        onOpenModal,
        save,
        isLoading,
        studentEnrolmentStatuses,
        list,
    };
};

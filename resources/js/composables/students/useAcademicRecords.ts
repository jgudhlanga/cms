import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { AcademicRecord } from '@/types/students';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export const useAcademicRecords = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const getName = () => trans('trans.academic_record');
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const studentAbility = 'manageOwnStudentAcademicDetails:students';
    const adminAbility = 'manageStudentMetadata:admin';
    const allowed = hasAbility([adminAbility, studentAbility]);
    const createAcademicRecordColumns = () => {
        return [
            { header: trans_choice('trans.school', 1), accessorKey: 'attributes.school' },
            { header: trans_choice('trans.place', 1), accessorKey: 'attributes.place' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: AcademicRecord } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(false, [
                        {
                            key: 'view',
                            action: () => {},
                        },
                        { key: 'edit', action: () => onOpenModal(row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(allowed, route('academic-records.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(allowed, route('academic-records.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(allowed, route('academic-records.force-delete', id), getName()),
                        },
                    ]);
                },
            },
        ];
    };

    const onOpenModal = (academicRecord?: AcademicRecord) => {
        if (!allowed) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.academic_records, edit: academicRecord });
    };

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;

    function validateForm(form: any) {
        mergeValidationSchema(schemaFields)(['placeSchema'], schemaFields['schoolSchema']()).parse(form);
    }

    const updateAcademicRecord = (form: InertiaForm<any>, academicRecord?: AcademicRecord) => {
        try {
            validateForm(form);
            const id = getIdParams(academicRecord?.id?.toString() ?? '');
            form.put(
                route('academic-records.update', id),
                buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.academic_records),
            );
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const createAcademicRecord = (form: InertiaForm<any>) => {
        try {
            validateForm(form);
            form.post(route('academic-records.store'), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.academic_records));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return {
        createAcademicRecordColumns,
        onOpenModal,
        updateAcademicRecord,
        createAcademicRecord,
        allowed,
    };
};

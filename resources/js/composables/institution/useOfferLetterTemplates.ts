import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { IntakePeriod, OfferLetterTemplate, OfferLetterTemplateParams } from '@/types/institution';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

const dash = '—';

export const useOfferLetterTemplates = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, anchorTag } = useDataTables();
    const { navigateTo } = useUtils();

    const createOfferLetterTemplateColumns = (intakePeriod: IntakePeriod) => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        const intakeId = getIdParams(intakePeriod.id?.toString() ?? '');

        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            {
                header: trans_choice('trans.department_code', 2),
                accessorKey: 'attributes.departments',
                cell: ({ row }: { row: { original: OfferLetterTemplate } }) => row.original.attributes?.departments || dash,
            },
            {
                header: trans_choice('trans.level', 2),
                accessorKey: 'attributes.levels',
                cell: ({ row }: { row: { original: OfferLetterTemplate } }) => row.original.attributes?.levels || dash,
            },
            {
                header: trans_choice('trans.mode_of_study', 1),
                accessorKey: 'attributes.modeOfStudy',
                cell: ({ row }: { row: { original: OfferLetterTemplate } }) => row.original.attributes?.modeOfStudy || dash,
            },
            {
                header: trans('trans.offer_letter_template_tuition_override'),
                accessorKey: 'attributes.tuitionOverride',
                cell: ({ row }: { row: { original: OfferLetterTemplate } }) => row.original.attributes?.tuitionOverride || dash,
            },
            {
                header: trans('trans.preview'),
                accessorKey: 'preview',
                enableSorting: false,
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: OfferLetterTemplate } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return anchorTag({
                        title: trans('trans.preview'),
                        href: route('intake-periods.offer-letter-templates.preview', {
                            intake_period: intakeId,
                            offer_letter_template: id,
                        }),
                        classes: 'btn btn-sm btn-primary',
                    });
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: OfferLetterTemplate } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.offer_letter_template', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'edit',
                            action: () =>
                                navigateTo(
                                    route('intake-periods.offer-letter-templates.edit', {
                                        intake_period: intakeId,
                                        offer_letter_template: id,
                                    }),
                                ),
                        },
                        {
                            key: 'archive',
                            action: () =>
                                onDelete(
                                    can['update:intake-periods'],
                                    route('intake-periods.offer-letter-templates.destroy', {
                                        intake_period: intakeId,
                                        offer_letter_template: id,
                                    }),
                                    name,
                                ),
                        },
                        {
                            key: 'restore',
                            action: () =>
                                onRestore(
                                    can['update:intake-periods'],
                                    route('intake-periods.offer-letter-templates.restore', {
                                        intake_period: intakeId,
                                        offer_letter_template: id,
                                    }),
                                    name,
                                ),
                        },
                        {
                            key: 'delete',
                            action: () =>
                                onForceDelete(
                                    can['update:intake-periods'],
                                    route('intake-periods.offer-letter-templates.force-delete', {
                                        intake_period: intakeId,
                                        offer_letter_template: id,
                                    }),
                                    name,
                                ),
                        },
                    ]);
                },
            },
        ];
    };

    const saveOfferLetterTemplate = (
        form: InertiaForm<OfferLetterTemplateParams>,
        intakePeriod: IntakePeriod,
        offerLetterTemplate?: OfferLetterTemplate,
    ) => {
        const intakeId = getIdParams(intakePeriod.id?.toString() ?? '');
        const success = trans('trans.item_saved', { item: trans_choice('trans.offer_letter_template', 1) });
        const error = trans('trans.item_save_failure', { item: trans_choice('trans.offer_letter_template', 1) });
        if (offerLetterTemplate) {
            const id = getIdParams(offerLetterTemplate.id?.toString() ?? '');
            form.put(
                route('intake-periods.offer-letter-templates.update', {
                    intake_period: intakeId,
                    offer_letter_template: id,
                }),
                buildFormOptions(form, success, error),
            );
        } else {
            form.post(route('intake-periods.offer-letter-templates.store', intakeId), buildFormOptions(form, success, error));
        }
    };

    return {
        createOfferLetterTemplateColumns,
        saveOfferLetterTemplate,
    };
};

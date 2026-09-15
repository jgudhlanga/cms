import { IconName } from '@/enums/icons';
import type { ActionMenuGroup, ActionMenuItem } from '@/types/buttons';
import type { ButtonDropdownOption } from '@/types/tables';
import { trans, trans_choice } from 'laravel-vue-i18n';

/**
 * Adapts the legacy DataTable `{ key, action }` options into the grouped
 * `DropdownButton` menu shape introduced for class action menus.
 *
 * Unknown keys are skipped rather than rendered as blank rows, so a typo in a
 * column definition does not leave an empty trigger.
 */

type TableActionDefinition = {
    label: () => string;
    icon: IconName;
    danger?: boolean;
};

const TABLE_ACTION_DEFINITIONS: Record<string, TableActionDefinition> = {
    view: {
        label: () => trans('trans.view'),
        icon: IconName.eye,
    },
    edit: {
        label: () => trans('trans.edit'),
        icon: IconName.edit,
    },
    archive: {
        label: () => trans_choice('trans.archive', 1),
        icon: IconName.archive,
    },
    restore: {
        label: () => trans('trans.restore'),
        icon: IconName.restore,
    },
    delete: {
        label: () => trans('trans.force_delete'),
        icon: IconName.trash,
        danger: true,
    },
    approve: {
        label: () => trans('hms.approve_application'),
        icon: IconName.check_box,
    },
    decline: {
        label: () => trans('hms.decline_application'),
        icon: IconName.danger,
        danger: true,
    },
    reassign: {
        label: () => trans('hms.reassign_room'),
        icon: IconName.edit,
    },
};

export const filterTableActionOptions = (
    isArchived: boolean,
    options: Array<ButtonDropdownOption>,
): Array<ButtonDropdownOption> => {
    if (isArchived) {
        return options.filter((item) => item.key === 'restore');
    }

    return options.filter((item) => item.key !== 'restore');
};

export const buildTableActionMenuGroups = (
    isArchived: boolean,
    options: Array<ButtonDropdownOption>,
): ActionMenuGroup[] => {
    const items: ActionMenuItem[] = filterTableActionOptions(isArchived, options).flatMap((option) => {
        const definition = TABLE_ACTION_DEFINITIONS[option.key];
        if (!definition) {
            return [];
        }

        return [
            {
                key: option.key,
                label: definition.label(),
                icon: definition.icon,
                action: option.action,
                danger: definition.danger,
            },
        ];
    });

    if (items.length === 0) {
        return [];
    }

    return [{ key: 'actions', items }];
};

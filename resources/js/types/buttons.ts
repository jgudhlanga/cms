import { IconName } from '@/enums/icons';

/**
 * A single entry in an actions dropdown.
 *
 * Availability is expressed in two different ways, and the distinction matters:
 *
 * - Permission-gated actions must be left out of the `items` array entirely. A
 *   disabled row still tells the viewer the capability exists.
 * - State-gated actions (not applicable yet, nothing selected, already done)
 *   stay in the list with `disabled` set and a `disabledReason` explaining why,
 *   so the menu keeps a stable shape as the underlying state changes.
 */
export type ActionMenuItem = {
    key: string;
    label: string;
    /** One muted line under the label saying what the action actually does. */
    description?: string;
    icon?: IconName;
    /** Inertia navigation. Ignored while `disabled`. */
    href?: string;
    /** Plain anchor opened in a new tab, e.g. a file download. Ignored while `disabled`. */
    externalHref?: string;
    /** Click handler, used when neither href is set. Not called while `disabled`. */
    action?: () => void;
    disabled?: boolean;
    /** Shown beneath the label, and mirrored into `title`, when `disabled`. */
    disabledReason?: string;
    /** Renders the item in the destructive colour. */
    danger?: boolean;
};

/** A labelled section of an actions dropdown. Groups with no items are dropped. */
export type ActionMenuGroup = {
    key: string;
    label?: string;
    items: ActionMenuItem[];
};

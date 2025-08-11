import type { ColorVariant } from '@/enums/colors';
import type { IconName } from '@/enums/icons';
import { Component, type VNodeChild } from 'vue';

export type Link = {
    transName?: string;
    url?: string;
    title?: string;
    name?: string;
    params?: any;
    icon?: string;
    group?: string;
    children?: Array<Link>;
};
export type Dropdown = {
    id: string;
    title: string;
    name?: string;
    description?: string;
};

export type SelectOption = {
    auxLabel?: string;
    label: string;
    relationshipOneLabel?: string;
    relationshipOneValue?: string;
    value: string | number;
};

export type DropdownButtonOption = {
    label: string;
    key: string;
    icon: () => VNodeChild;
};

export type ActionOption = {
    name: string;
    title: string;
};

export type ItemOptionSelectParams = {
    option: DropdownButtonOption;
    editAction: () => void;
    deleteAction: () => void;
    goBackAction: () => void;
    viewAction: () => void;
    importAction: () => void;
    createAction: () => void;
    archiveAction: () => void;
    restoreAction: () => void;
};

export type IconButtonParams = {
    action: () => void;
    disable: boolean | number | null;
    icon: IconName;
    type?: ColorVariant;
    iconColor?: ColorVariant;
    iconSize?: string;
};

export type NotificationParam = {
    title: string;
    description: string;
};
export type TableActionButton = {
    isArchived?: boolean;
    hasView?: boolean;
    onEdit?: () => void | null;
    onView?: () => void | null;
    onArchive?: () => void | null;
    onRestore?: () => void | null;
    onDelete?: () => void | null;
};

export type TableButton = {
    title: string;
    variant?: ColorVariant;
    classes?: string;
    onClick?: () => void | null;
};
export type ValueAndLabel = {
    value: any;
    transKey?: string;
    transChoiceKey?: string;
    isBoolean?: boolean;
};
export type CustomTab = {
    label?: string;
    transLabel?: () => string;
    value: string;
    component: Component;
    show?: boolean;
    icon?: IconName,
};
export type TimelineStep = {
    title: string;
    description?: string;
    timelineMarker?: string;
    label?: string;
    component: Component;
    onClick?: () => void | null;
    props?: any,
    status?: 'active' | 'completed' | 'pending' | 'failed';
};

export type AccordionItemProps = {
    value: string;
    title: () => string;
    description?: () => string;
    content: Component
}

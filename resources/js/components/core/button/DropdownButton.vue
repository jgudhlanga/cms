<script lang="ts" setup>
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { ButtonDropdownOption, TableActionOption } from '@/types/tables';
import TransText from '../util/TransText.vue';
import BaseButton from './BaseButton.vue';
import MoreButton from './MoreButton.vue';

const props = withDefaults(
    defineProps<{
        groupTitle?: string;
        onlyIcon?: boolean;
        icon?: IconName;
        options: Array<ButtonDropdownOption>;
    }>(),
    {
        options: () => [
            {
                key: 'edit',
                action: () => console.warn('No action implemented'),
            },
        ],
    },
);

const getOptions = () => {
    const params = props.options;
    const options: Array<TableActionOption> = [];

    params.map((option: ButtonDropdownOption) => {
        if (option.key == 'edit') {
            options.push({ key: 'edit', icon: IconName.edit, transKey: 'trans.edit', action: () => option.action() });
        }
        if (option.key == 'archive') {
            options.push({
                key: 'archive',
                icon: IconName.archive,
                action: option.action,
                transChoiceKey: 'trans.archive',
            });
        }
        if (option.key == 'delete') {
            options.push({
                key: 'delete',
                icon: IconName.trash,
                action: option.action,
                transKey: 'trans.force_delete',
            });
        }
        if (option.key == 'restore') {
            options.push({ key: 'restore', icon: IconName.restore, action: option.action, transKey: 'trans.restore' });
        }
        if (option.key == 'view') {
            options.push({ key: 'view', icon: IconName.eye, action: option.action, transKey: 'trans.view' });
        }
    });
    return options;
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <MoreButton v-if="onlyIcon" :icon="icon" class="text-primary" />
            <BaseButton v-else :variant="ColorVariant.shade_outline">
                <component :is="icons[icon as IconName]" size="12" />
                {{ groupTitle }}
            </BaseButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent>
            <DropdownMenuGroup>
                <DropdownMenuItem v-for="option in getOptions()" :key="option.key">
                    <button class="flex w-full items-center space-x-2" @click="() => option.action()">
                        <component :is="icons[option.icon as IconName]" size="12" />
                        <TransText :item="option" :key-index="1" />
                    </button>
                </DropdownMenuItem>
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

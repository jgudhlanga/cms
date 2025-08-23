<script lang="ts" setup>
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName, icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import TransText from '../util/TransText.vue';
import BaseButton from './BaseButton.vue';
import MoreButton from './MoreButton.vue';
interface Props {
    options: Array<any>;
    groupTitle?: string;
    icon?: IconName;
    showOnlyIcon?: boolean;
    showGroupIcon?: boolean;
    disabled?: boolean;
    classes?: string;
}

defineProps<Props>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <MoreButton  :disabled="disabled" :icon="icon" v-if="showOnlyIcon" />
            <BaseButton
                v-else
                :disabled="disabled"
                :title="groupTitle"
                :variant="ColorVariant.primary"
                :size="ButtonSize.xs"
                :classes="cn('rounded-full', classes)"
            >
                <MoreButton :icon="icon" v-if="showGroupIcon" />
            </BaseButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent>
            <DropdownMenuGroup>
                <DropdownMenuItem v-for="option in options" :key="option.key">
                    <button class="flex w-full items-center space-x-2" @click="() => option?.action!()">
                        <component :is="icons[option.icon as IconName]" size="12" />
                        <TransText :item="option" :key-index="1" />
                    </button>
                </DropdownMenuItem>
            </DropdownMenuGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

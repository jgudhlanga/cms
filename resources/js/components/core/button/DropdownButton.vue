<script lang="ts" setup>
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuGroup,
	DropdownMenuItem,
	DropdownMenuLabel,
	DropdownMenuSeparator,
	DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { icons } from '@/lib/icons';
import type { ActionMenuGroup, ActionMenuItem } from '@/types/buttons';
import { Link as InertiaLink } from '@inertiajs/vue3';
import { trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';
import BaseButton from './BaseButton.vue';
import MoreButton from './MoreButton.vue';

const props = withDefaults(
	defineProps<{
		groups?: ActionMenuGroup[];
		label?: string;
		icon?: IconName;
		onlyIcon?: boolean;
		size?: ButtonSize;
		variant?: ColorVariant;
		align?: 'start' | 'center' | 'end';
		contentClass?: string;
	}>(),
	{
		groups: () => [],
		onlyIcon: false,
		size: ButtonSize.xs,
		variant: ColorVariant.primary_outline,
		align: 'end'
	}
);

/**
 * Groups are filtered once here rather than in the template, so the list is not
 * rebuilt on every render. A group whose items are all gone disappears with it,
 * and when nothing is left the trigger itself is not rendered.
 */
const visibleGroups = computed(() => props.groups.filter((group) => group.items.length > 0));

const hasActions = computed(() => visibleGroups.value.length > 0);

const triggerLabel = computed(() => props.label ?? trans_choice('trans.action', 2));

const isLink = (item: ActionMenuItem): boolean =>
	!item.disabled && (item.href != null || item.externalHref != null);

/**
 * A disabled item is deliberately rendered as a plain span rather than a dead
 * anchor: leaving the href in place would still be middle-clickable and
 * copyable from the browser's context menu.
 */
const itemTag = (item: ActionMenuItem) => {
	if (item.disabled) {
		return 'span';
	}

	if (item.href) {
		return InertiaLink;
	}

	return item.externalHref ? 'a' : 'span';
};

const itemBindings = (item: ActionMenuItem): Record<string, unknown> => {
	if (item.disabled) {
		return {};
	}

	if (item.href) {
		return { href: item.href };
	}

	return item.externalHref ? { href: item.externalHref, target: '_blank', rel: 'noopener noreferrer' } : {};
};

/** The reason an item cannot be used outranks its description. */
const itemSubLine = (item: ActionMenuItem): string | undefined =>
	item.disabled ? item.disabledReason : item.description;

const itemClass = (item: ActionMenuItem): string =>
	[
		'flex w-full items-start gap-2',
		item.disabled ? 'cursor-not-allowed' : 'cursor-pointer',
		item.danger && !item.disabled ? 'text-destructive' : ''
	]
		.filter(Boolean)
		.join(' ');

const onSelect = (item: ActionMenuItem): void => {
	if (item.disabled) {
		return;
	}

	item.action?.();
};
</script>

<template>
	<DropdownMenu v-if="hasActions">
		<DropdownMenuTrigger as-child>
			<MoreButton v-if="onlyIcon" :icon="icon" class="text-primary" :aria-label="triggerLabel" />
			<BaseButton v-else type="button" :size="size" :variant="variant" classes="rounded-full">
				<component :is="icons[icon]" v-if="icon" class="h-3.5 w-3.5 text-current" />
				{{ triggerLabel }}
				<component :is="icons[IconName.chevron_down]" class="h-3 w-3 text-current" />
			</BaseButton>
		</DropdownMenuTrigger>
		<DropdownMenuContent :align="align" :class="contentClass ?? 'min-w-56'">
			<template v-for="(group, groupIndex) in visibleGroups" :key="group.key">
				<DropdownMenuSeparator v-if="groupIndex > 0" />
				<DropdownMenuLabel v-if="group.label" class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
					{{ group.label }}
				</DropdownMenuLabel>
				<DropdownMenuGroup>
					<DropdownMenuItem
						v-for="item in group.items"
						:key="item.key"
						:disabled="item.disabled"
						:as-child="isLink(item)"
						:title="item.disabled ? item.disabledReason : undefined"
						@select="onSelect(item)"
					>
						<component :is="itemTag(item)" v-bind="itemBindings(item)" :class="itemClass(item)">
							<component :is="icons[item.icon]" v-if="item.icon" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-current" />
							<span class="min-w-0 flex-1">
								{{ item.label }}
								<span v-if="itemSubLine(item)" class="block text-[11px] font-normal text-muted-foreground">
									{{ itemSubLine(item) }}
								</span>
							</span>
						</component>
					</DropdownMenuItem>
				</DropdownMenuGroup>
			</template>
		</DropdownMenuContent>
	</DropdownMenu>
</template>

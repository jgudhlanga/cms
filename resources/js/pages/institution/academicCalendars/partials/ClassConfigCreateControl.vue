<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import type { ClassConfigPeriodOption } from '@/types/academic-calendar';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    remainingPeriods: ClassConfigPeriodOption[];
}>();

const emit = defineEmits<{
    create: [period: ClassConfigPeriodOption];
}>();

const currentPeriod = computed(() => props.remainingPeriods.find((period) => period.isCurrent) ?? null);
const otherPeriods = computed(() => props.remainingPeriods.filter((period) => !period.isCurrent));
const showPrimary = computed(() => currentPeriod.value != null);
const dropdownPeriods = computed(() => (showPrimary.value ? otherPeriods.value : props.remainingPeriods));
const showDropdown = computed(() => dropdownPeriods.value.length > 0);
</script>

<template>
    <div v-if="remainingPeriods.length > 0" class="inline-flex items-center">
        <BaseButton
            v-if="showPrimary && currentPeriod"
            type="button"
            :size="ButtonSize.xs"
            :variant="ColorVariant.shade_outline"
            :classes="showDropdown ? 'rounded-l-full rounded-r-none px-2' : 'rounded-full px-2'"
            :aria-label="trans('academic_calendar.add_class_config_period', { period: currentPeriod.name })"
            @click="emit('create', currentPeriod)"
        >
            <BaseIcon :name="IconName.add" class="h-3.5 w-3.5 text-current" />
        </BaseButton>
        <DropdownMenu v-if="showDropdown">
            <DropdownMenuTrigger as-child>
                <BaseButton
                    type="button"
                    :size="ButtonSize.xs"
                    :variant="ColorVariant.shade_outline"
                    :classes="showPrimary ? 'rounded-r-full rounded-l-none -ml-px px-1.5' : 'rounded-full px-2'"
                    :aria-label="trans('academic_calendar.add_class_config')"
                >
                    <BaseIcon v-if="!showPrimary" :name="IconName.add" class="h-3.5 w-3.5 text-current" />
                    <BaseIcon :name="IconName.chevron_down" class="h-3 w-3 text-current" />
                </BaseButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuGroup>
                    <DropdownMenuItem v-for="period in dropdownPeriods" :key="String(period.id)">
                        <button type="button" class="flex w-full items-center" @click="emit('create', period)">
                            {{ trans('academic_calendar.add_class_config_period', { period: period.name }) }}
                        </button>
                    </DropdownMenuItem>
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>

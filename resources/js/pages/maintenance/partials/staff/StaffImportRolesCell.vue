<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import type { StaffImportLookupField, StaffImportLookupOption } from '@/types/staff-import';
import customAxios from '@/services/http-init';
import { trans } from 'laravel-vue-i18n';
import { ChevronsUpDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    roles: StaffImportLookupField[];
    options: StaffImportLookupOption[];
    modelValue: number[];
    createdRoleNames: Set<string>;
}>();

const emit = defineEmits<{
    'update:modelValue': [number[]];
    created: [StaffImportLookupOption];
}>();

const open = ref(false);
const creatingRoleName = ref<string | null>(null);
const createError = ref<string | null>(null);

const ROLE_GROUP_ORDER = [
    'Academic',
    'Administrative',
    'Managerial',
    'Service and support',
] as const;

const OTHER_ROLE_GROUP = 'Other';

const groupedRoleOptions = computed((): Array<{ group: string; options: StaffImportLookupOption[] }> => {
    const buckets = new Map<string, StaffImportLookupOption[]>();

    for (const option of props.options) {
        const group = option.roleGroup?.trim() !== '' ? option.roleGroup!.trim() : OTHER_ROLE_GROUP;
        const existing = buckets.get(group) ?? [];
        existing.push(option);
        buckets.set(group, existing);
    }

    const orderedGroups: Array<{ group: string; options: StaffImportLookupOption[] }> = [];

    for (const group of ROLE_GROUP_ORDER) {
        const options = buckets.get(group);

        if (options !== undefined && options.length > 0) {
            orderedGroups.push({ group, options });
            buckets.delete(group);
        }
    }

    for (const [group, options] of buckets.entries()) {
        if (options.length > 0) {
            orderedGroups.push({ group, options });
        }
    }

    return orderedGroups;
});

const unresolvedRoles = computed((): StaffImportLookupField[] => {
    return props.roles.filter((role) => role.resolvedId === null && role.raw.trim() !== '');
});

const selectedLabels = computed((): string[] => {
    return props.options
        .filter((option) => props.modelValue.includes(option.value))
        .map((option) => option.label);
});

const triggerLabel = computed((): string => {
    if (selectedLabels.value.length === 0) {
        return trans('trans.maintenance_staff_import_select_lookup');
    }

    if (selectedLabels.value.length <= 2) {
        return selectedLabels.value.join(', ');
    }

    return trans('trans.maintenance_staff_import_roles_selected', {
        count: String(selectedLabels.value.length),
    });
});

const triggerClass = computed((): string => {
    if (unresolvedRoles.value.length > 0 && props.modelValue.length === 0) {
        return 'border-destructive bg-destructive/5';
    }

    if (props.roles.some((role) => role.needsReview)) {
        return 'border-amber-500 bg-amber-50';
    }

    if (props.createdRoleNames.size > 0) {
        return 'border-green-500 bg-green-50';
    }

    return 'border-border';
});

const isSelected = (roleId: number): boolean => props.modelValue.includes(roleId);

const toggleRole = (roleId: number, checked: boolean): void => {
    if (checked) {
        emit('update:modelValue', [...new Set([...props.modelValue, roleId])]);
        return;
    }

    emit(
        'update:modelValue',
        props.modelValue.filter((id) => id !== roleId),
    );
};

const createRole = async (name: string): Promise<void> => {
    const trimmedName = name.trim();

    if (trimmedName === '') {
        return;
    }

    creatingRoleName.value = trimmedName;
    createError.value = null;

    try {
        const response = await customAxios('').post<StaffImportLookupOption>(
            route('maintenance.staff-import.lookups.create'),
            {
                type: 'role',
                name: trimmedName,
            },
        );

        const nextRoleIds = [...new Set([...props.modelValue, response.data.value])];

        emit('created', response.data);
        emit('update:modelValue', nextRoleIds);
    } catch (caught) {
        const responseData = (caught as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })
            .response?.data;

        createError.value =
            responseData?.errors?.name?.[0]
            ?? responseData?.message
            ?? trans('trans.maintenance_staff_import_preview_failed');
    } finally {
        creatingRoleName.value = null;
    }
};
</script>

<template>
    <div class="min-w-36 space-y-0.5">
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <button
                    type="button"
                    class="flex h-7 w-full items-center justify-between gap-1 rounded border bg-background px-1.5 text-left text-[10px]"
                    :class="triggerClass"
                >
                    <span class="truncate">{{ triggerLabel }}</span>
                    <ChevronsUpDown class="h-3 w-3 shrink-0 opacity-50" />
                </button>
            </PopoverTrigger>
            <PopoverContent class="z-50 w-64 p-2" align="start">
                <p class="mb-1 px-1 text-[10px] font-medium text-muted-foreground">
                    {{ $tChoice('trans.role', 2) }}
                </p>
                <div class="max-h-64 space-y-2 overflow-y-auto">
                    <div
                        v-for="group in groupedRoleOptions"
                        :key="group.group"
                        class="space-y-0.5"
                    >
                        <p class="px-1 text-[10px] font-semibold uppercase text-muted-foreground">
                            {{ group.group }}
                        </p>
                        <label
                            v-for="option in group.options"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-2 rounded px-1 py-1 text-[10px] hover:bg-muted"
                        >
                            <Checkbox
                                class="size-3.5"
                                :checked="isSelected(option.value)"
                                @update:checked="toggleRole(option.value, $event === true)"
                            />
                            <span class="leading-tight">{{ option.label }}</span>
                        </label>
                    </div>
                </div>
            </PopoverContent>
        </Popover>

        <button
            v-for="role in unresolvedRoles"
            :key="role.raw"
            type="button"
            class="block text-left text-[10px] text-primary hover:underline disabled:opacity-50"
            :disabled="creatingRoleName === role.raw"
            @click="createRole(role.raw)"
        >
            {{ $t('trans.maintenance_staff_import_create_lookup', { name: role.raw }) }}
        </button>

        <p v-if="createdRoleNames.size > 0" class="text-[10px] text-green-700">
            {{ $t('trans.maintenance_staff_import_lookup_created') }}
        </p>

        <p v-if="createError" class="text-[10px] text-destructive">{{ createError }}</p>
    </div>
</template>

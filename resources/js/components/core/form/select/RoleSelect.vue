<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { useRoles } from '@/composables/acl/useRoles';
import { Role } from '@/types/acl';
import { SelectOption } from '@/types/utils';
import { computed, onMounted } from 'vue';

interface Props {
    label?: string;
    placeholder?: string;
    isClearable?: boolean;
    isMulti?: boolean;
    isSearchable?: boolean;
    loading?: boolean;
    error?: string | object;
}

defineProps<Props>();
const { isLoading, listRoles, roles } = useRoles();

onMounted(async () => {
    await listRoles();
});

const options = computed(() => {
    return roles.value.map(
        (role: Role) =>
            <SelectOption>{
                value: Number(role.id),
                label: role?.attributes?.name,
            },
    );
});
</script>

<template>
    <BaseSelect
        :label="$tChoice('trans.role', 1)"
        :placeholder="placeholder"
        v-bind="$attrs"
        :is-multi="isMulti"
        :loading="isLoading"
        :options="options" />
</template>

<script setup lang="ts">
import { BaseSelect } from '@/components/core/form';
import { useUsers } from '@/composables/users/useUsers';
import { User } from '@/types/users';
import { SelectOption } from '@/types/utils';
import { computed, onMounted } from 'vue';

interface Props {
    url: string;
    label?: string;
    placeholder?: string;
    isClearable?: boolean;
    isMulti?: boolean;
    isSearchable?: boolean;
    loading?: boolean;
    error?: string | object;
}

const props = defineProps<Props>();
const { url } = props;
const { isLoading, loadUsers, users } = useUsers();

onMounted(async () => {
    await loadUsers(url);
});

const options = computed(() => {
    return users?.value?.data?.map(
        (user: User) =>
            <SelectOption>{
                value: Number(user.id),
                label: user?.attributes?.name,
            },
    );
});
</script>

<template>
    <BaseSelect
        :label="$tChoice('trans.user', 1)"
        :placeholder="placeholder"
        v-bind="$attrs"
        :is-multi="isMulti"
        :loading="isLoading"
        :options="options"
    />
</template>

<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import UserForm from '@/pages/users/partials/UserForm.vue';
import { User } from '@/types/users';
import { Head } from '@inertiajs/vue3';

interface Props {
    user: User;
}

const props = defineProps<Props>();
const { user } = props;
const breadcrumbs = [{ transChoiceKey: 'user', href: route('users.index') }, { title: user?.attributes?.name }, { transKey: 'edit' }];
</script>

<template>
    <Head :title="$tChoice('trans.user', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <UserForm :user="user" :edit="true" />
    </PageContainer>
</template>

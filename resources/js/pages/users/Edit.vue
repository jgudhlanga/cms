<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { Head } from '@inertiajs/vue3';
import { User } from '@/types/users';
import UserForm from '@/pages/users/partials/UserForm.vue';
import StudentForm from '@/pages/users/partials/StudentForm.vue';
import { useUtils } from '@/composables/core/useUtils';

interface Props {
    user: User;
}

const props = defineProps<Props>();
const { user } = props;
const breadcrumbs = [{ transChoiceKey: 'user', href: route('users.index') }, { title: user?.attributes?.name }, { transKey: 'edit' }];
const {isItTrue } = useUtils()
</script>

<template>
    <Head :title="$tChoice('trans.user', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <StudentForm :user="user" v-if="isItTrue(user?.attributes?.hasStudentProfile)" />
        <UserForm v-else :user="user" :edit="true" />
    </PageContainer>
</template>

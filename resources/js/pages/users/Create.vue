<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import UserForm from '@/pages/users/partials/UserForm.vue';
import { Head } from '@inertiajs/vue3';

const breadcrumbs = [{ transChoiceKey: 'user', href: route('users.index') }, { transKey: 'create' }];
</script>

<template>
    <Head :title="$tChoice('trans.user', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <UserForm />
    </PageContainer>
</template>

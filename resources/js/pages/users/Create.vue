<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import { Head } from '@inertiajs/vue3';
import Form from '@/pages/users/partials/Form.vue';

const breadcrumbs = [{ transChoiceKey: 'user', href: route('users.index') }, { transKey: 'create' }];
</script>

<template>
    <Head :title="$tChoice('trans.user', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <Form/>
    </PageContainer>
</template>

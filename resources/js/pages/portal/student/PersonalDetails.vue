<script setup lang="ts">
import PageContainer from '@/components/core/page/PageContainer.vue';
import PersonalDetails from '@/components/students/view/PersonalDetails.vue';
import { AuthObject } from '@/types/data-pagination';
import { PersonalDetailView, Student } from '@/types/students';
import { BreadcrumbItemInterface } from '@/types/ui';
import { Head } from '@inertiajs/vue3';

interface Props {
    student: Student;
    auth: AuthObject;
    errors: object;
}

const props = defineProps<Props>();
const { user } = props.auth;
const { student } = props;
const breadcrumbs: BreadcrumbItemInterface[] = [{ title: user.attributes?.name }, { transKey: 'personal_details' }];
const personal: PersonalDetailView = {
    title: student?.title ?? '',
    firstname: user?.attributes?.firstname ?? '',
    middleName: user?.attributes?.middleName ?? '',
    lastname: user?.attributes?.lastname ?? '',
    gender: student?.gender ?? '',
    maritalStatus: student?.maritalStatus ?? '',
    idType: student?.idType ?? '',
    idNumber: student?.idNumber ?? '',
    passportNumber: student?.passportNumber ?? '',
    country: student?.country ?? '',
    studyPermitNumber: student?.studentPermitNumber ?? '' ?? '',
    dateOfBirth: student?.dateOfBirth ?? '' ?? '',
    showAvatar: true,
    avatarUrl: user?.attributes?.avatarUrl,
};
</script>
<template>
    <Head :title="`${$t('trans.personal_details')} ${$t('trans.details')}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <PersonalDetails :personal="personal" :show-extra="true" />
    </PageContainer>
</template>

<script setup lang="ts">
import BaseCard from '@/components/core/card/BaseCard.vue';
import BaseImage from '@/components/core/image/BaseImage.vue';
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
    firstname: user.firstname ?? '',
    middleName: user?.middleName ?? '',
    lastname: user.lastname ?? '',
    gender: student?.gender ?? '',
    maritalStatus: student?.maritalStatus ?? '',
    idType: student?.idType ?? '',
    idNumber: student?.idNumber ?? '',
    passportNumber: student?.passportNumber ?? '',
    country: student?.country ?? '',
    studyPermitNumber: student?.studentPermitNumber ?? '' ?? '',
    dateOfBirth: student?.dateOfBirth ?? '' ?? '',
};
</script>
<template>
    <Head :title="`${$t('trans.personal_details')} ${$t('trans.details')}`" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <BaseCard>
            <div class="flex">
                <div class="flex overflow-hidden">
                    <BaseImage :src="''" :is-person="true" classes="w-[130px] h-[130px] rounded-full border-[1px] border-primary shadow-lg" />
                </div>
                <PersonalDetails :personal="personal" />
            </div>
        </BaseCard>
    </PageContainer>
</template>

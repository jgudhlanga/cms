<script lang="ts" setup>
import { BaseButton } from '@/components/core/button';
import BaseCard from '@/components/core/card/BaseCard.vue';
import { BaseInput } from '@/components/core/form';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import PageContainer from '@/components/core/page/PageContainer.vue';
import AnimatedCheckMark from '@/components/core/util/AnimatedCheckMark.vue';
import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/enums/icons';
import { errorAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { AuthObject } from '@/types/data-pagination';
import { EnrolmentLookup } from '@/types/enrolments';
import { Link } from '@/types/ui';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AnimatedErrorIcon from '@/components/core/util/AnimatedErrorIcon.vue';

interface Props {
    auth: AuthObject;
    errors: object;
}

defineProps<Props>();

const breadcrumbs: Array<Link> = [
    { transKey: 'dashboard', href: route('dashboard') },
    { transChoiceKey: 'enrolment', href: route('enrolments.index') },
    { title: 'Enrolment Lookup' },
];

const isSearching = ref(false);
const search = ref('');
const enrolmentLookup = ref<EnrolmentLookup | null>(null);
const { navigateTo } = useUtils();
const searchProfile = async () => {
    if (search.value === '') {
        errorAlert('Please enter a search term');
        return;
    }
    isSearching.value = true;
    try {
        enrolmentLookup.value = await HttpService.post(route('enrolments.search-profile'), { search: search.value });
    } catch (error: any) {
        const message = error?.response?.data?.message;
        if (message) {
            errorAlert(error?.response?.data?.message);
        } else {
            errorAlert('An error occurred while searching profile. Try again later.');
        }
    } finally {
        isSearching.value = false;
    }
};
const colorVariant = computed(() => {
    if (enrolmentLookup.value && String(enrolmentLookup.value?.statusCode) == '200') {
        return 'green-600';
    }
    return 'red-600';
});

const title = computed(() => {
    if (enrolmentLookup.value && String(enrolmentLookup.value?.statusCode) == '200') {
        return 'Profile found';
    }
    return 'Profile not found';
});

const profileFound = computed(() => {
    return enrolmentLookup.value && String(enrolmentLookup.value?.statusCode) == '200';

});
</script>

<template>
    <Head :title="$tChoice('enrolment', 2)" />
    <PageContainer :breadcrumbs="breadcrumbs">
        <div class="my-5 flex flex-col space-y-5">
            <BaseCard title="Search Applicant details" description="Search for existing user account / student profile">
                <div class="mx-auto my-5 flex w-full flex-col">
                    <div class="flex w-2/3 flex-col">
                        <div class="flex w-full flex-col">
                            <BaseInput
                                classes="w-full p-6"
                                input-id="order_reference"
                                label="Email address / National ID# / Passport# / Student# / Order Reference / Payment Reference"
                                v-model="search"
                                placeholder="email address / national id# / passport# / student# / order reference / payment reference"
                                :vertical-layout="true"
                                :label-uppercase="false"
                                :is-required="true"
                            />
                        </div>
                        <div class="mt-6 flex w-full md:w-auto">
                            <BaseButton @click="searchProfile" type="button" :processing="isSearching">
                                <BaseIcon :name="IconName.search" />
                                Search user account / Student Profile
                            </BaseButton>
                        </div>
                    </div>
                </div>
            </BaseCard>
        </div>
        <div class="my-5 flex w-full flex-col" v-if="enrolmentLookup">
            <BaseCard :title="title" :description="enrolmentLookup?.message ?? ''" :color-variant="colorVariant">
                <div class="flex justify-between transition-all duration-300">
                    <div class="flex flex-col">Content</div>
                    <AnimatedCheckMark v-if="profileFound" />
                    <AnimatedErrorIcon v-else />
                </div>
            </BaseCard>
        </div>
    </PageContainer>
</template>

<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import { BaseInput } from '@/components/core/form';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { useUtils } from '@/composables/core/useUtils';
import { IconName } from '@/enums/icons';
import { errorAlert } from '@/lib/alerts';
import HttpService from '@/services/http.service';
import { ref } from 'vue';

const isSearching = ref(false);
const search = ref('');
const { navigateTo } = useUtils();
const searchProfile = async () => {
    if (search.value === '') {
        errorAlert('Please enter a search term');
        return;
    }
    isSearching.value = true;
    try {
        const response = await HttpService.post(route('enrolments.search-profile'), { search: search.value });
        const message = response?.message;
        if (message) {
            // create a new user account and student profile and the application finally
            navigateTo(route('enrolments.create'));
        } else {
        }
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
</script>

<template>
    <div class="mx-auto my-5 flex w-full flex-col">
        <div class="flex w-2/3 flex-col">
            <div class="flex w-full flex-col">
                <BaseInput
                    classes="w-full p-6"
                    input-id="order_reference"
                    label="Email address / National ID# / Passport# / Student#"
                    v-model="search"
                    placeholder="email address / national id# / passport# / student#"
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
</template>

<script setup lang="ts">
import { User } from '@/types/users';

import { Head } from '@inertiajs/vue3';
import ApplicationCover from '@/pages/applications/ApplicationCover.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';

interface Props {
    user: User;
}
 defineProps<Props>();
</script>

<template>
    <Head :title="$t('trans.application_form')" />
    <ApplicationCover>

        <div class="flex items-center justify-center p-8">
           <div class="flex w-2/3">
               <BaseAlert
                   :type="TypeVariant.primary"
                   :title="$t('trans.success')"
                   :description="$t('trans.application_user_created_success_message', { email: user.attributes.email })"
               />
           </div>
        </div>
    </ApplicationCover>
</template>

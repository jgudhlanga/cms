<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { TypeVariant } from '@/enums/type-variants';
import ApplicationCover from '@/components/auth/ApplicationCover.vue';
import { Head } from '@inertiajs/vue3';

interface Props {
    email: string;
}

defineProps<Props>();
</script>

<template>
    <Head :title="$t('trans.application_form')" />
    <ApplicationCover>
        <div class="flex items-center justify-center p-3 md:p-8">
            <div class="flex w-full md:w-2/3">
                <BaseAlert
                    :type="TypeVariant.primary"
                    :title="$t('trans.success')"
                    :description="$t('trans.application_user_created_success_message', { email: email })"
                />
            </div>
        </div>
    </ApplicationCover>
</template>

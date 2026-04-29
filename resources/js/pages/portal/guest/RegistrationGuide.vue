<template>
    <div class="hidden h-screen items-center bg-gray-800 py-16 md:flex md:flex-1">
        <div class="mx-auto max-w-3xl space-y-6 p-6">
            <header class="text-center">
                <h1 class="text-2xl font-semibold text-white">{{ $t('trans.ui_application_steps') }}</h1>
                <p class="mt-2 text-gray-300">{{ $t('trans.ui_follow_these_steps_to_complete_your_application') }}</p>
            </header>

            <!-- Steps List -->
            <div class="space-y-6">
                <div v-for="step in steps" :key="step.id" class="rounded-2xl bg-white/5 p-6 shadow">
                    <div class="flex items-start gap-4">
                        <div class="text-primary flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                            <component :is="step.icon" class="h-6 w-6" />
                        </div>
                        <div>
                            <h2 class="text-lg font-medium text-white">{{ step.title }}</h2>
                            <p class="mt-1 text-sm text-gray-300">{{ step.subtitle }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ClipboardListIcon, CreditCardIcon, FileTextIcon, MailIcon, UserIcon } from 'lucide-vue-next';
import { ref } from 'vue';

const steps = ref([
    {
        id: 'create-account',
        title: 'Step 1 – Create an Account',
        subtitle: 'Register by providing your personal details to establish a profile within the portal.',
        icon: UserIcon,
    },
    {
        id: 'verify-email',
        title: 'Step 2 – Verify Email Address',
        subtitle: 'Confirm your email address by clicking the verification link sent to your inbox.',
        icon: MailIcon,
    },
    {
        id: 'choose-programs',
        title: 'Step 3 – Pay Application Fee',
        subtitle: 'Complete the required application fee payment through the secure online payment system.',
        icon: CreditCardIcon,
    },
    {
        id: 'complete-application',
        title: 'Step 4 – Complete Your Application',
        subtitle: 'Provide all required information and carefully review your application before submission.',
        icon: FileTextIcon,
    },
    {
        id: 'track-status',
        title: 'Step 5 – Track Application Status',
        subtitle: 'Log in to your account at any time to monitor the progress of your application and receive updates or update your application',
        icon: ClipboardListIcon,
    },
]);
</script>

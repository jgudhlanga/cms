<template>
    <div class="hidden md:flex md:flex-1 h-screen items-center bg-gray-800 py-16">
    <div class="max-w-3xl mx-auto p-6 space-y-6">
        <header class="text-center">
            <h1 class="text-2xl font-semibold text-white">Application Steps</h1>
            <p class="text-gray-300 mt-2">Follow these steps to complete your application.</p>
        </header>

        <!-- Steps List -->
        <div class="space-y-6">
            <div v-for="step in steps" :key="step.id" class="bg-white/5 p-6 rounded-2xl shadow">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 flex items-center justify-center rounded-full bg-indigo-100 text-primary">
                        <component :is="step.icon" class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-lg font-medium text-white">{{ step.title }}</h2>
                        <p class="text-sm text-gray-300 mt-1">{{ step.subtitle }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { UserIcon, MailIcon, CreditCardIcon, FileTextIcon, ClipboardListIcon } from 'lucide-vue-next'

const steps = ref([
    {
        id: 'create-account',
        title: 'Step 1 – Create an Account',
        subtitle: 'Register by providing your personal details to establish a profile within the portal.',
        icon: UserIcon
    },
    {
        id: 'verify-email',
        title: 'Step 2 – Verify Email Address',
        subtitle: 'Confirm your email address by clicking the verification link sent to your inbox.',
        icon: MailIcon
    },
    {
        id: 'choose-programs',
        title: 'Step 3 – Pay Application Fee',
        subtitle: 'Complete the required application fee payment through the secure online payment system.',
        icon: CreditCardIcon
    },
    {
        id: 'complete-application',
        title: 'Step 4 – Complete Your Application',
        subtitle: 'Provide all required information and carefully review your application before submission.',
        icon: FileTextIcon
    },
    {
        id: 'track-status',
        title: 'Step 5 – Track Application Status',
        subtitle: 'Log in to your account at any time to monitor the progress of your application and receive updates or update your application',
        icon: ClipboardListIcon
    }
])
</script>

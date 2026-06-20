<template>
    <div class="hidden min-h-svh items-center bg-sidebar py-10 text-sidebar-foreground lg:flex lg:flex-1">
        <div class="mx-auto max-w-md space-y-6 p-6 xl:max-w-lg">
            <header class="text-center">
                <h1 class="text-2xl font-semibold text-sidebar-accent-foreground">
                    {{ $t('trans.ui_application_steps') }}
                </h1>
                <p class="mt-2 text-sidebar-foreground">
                    {{ $t('trans.ui_follow_these_steps_to_complete_your_application') }}
                </p>
            </header>

            <div class="space-y-4">
                <div
                    v-for="step in visibleSteps"
                    :key="step.id"
                    class="rounded-2xl bg-sidebar-accent/20 p-5 shadow backdrop-blur-sm"
                    :class="step.id === highlightedStep ? 'ring-2 ring-sidebar-accent' : ''"
                >
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sidebar-accent/20 text-sidebar-accent">
                            <component :is="step.icon" class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-medium text-sidebar-accent-foreground">{{ step.title }}</h2>
                            <p class="mt-1 text-sm text-sidebar-foreground">{{ step.subtitle }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ClipboardListIcon, CreditCardIcon, FileTextIcon, LogInIcon, SearchIcon, UserIcon } from 'lucide-vue-next';
import { computed } from 'vue';

type EnrollmentPath = 'zimbabwean' | 'returning' | 'international';

const props = withDefaults(
    defineProps<{
        activePath?: EnrollmentPath;
    }>(),
    {
        activePath: 'zimbabwean',
    },
);

const newStudentSteps = [
    {
        id: 'verify-identity',
        title: 'Step 1 – Verify National ID',
        subtitle: 'Confirm your ID is not already registered before creating an account.',
        icon: SearchIcon,
    },
    {
        id: 'create-account',
        title: 'Step 2 – Create an Account',
        subtitle: 'Register with your personal details and secure password.',
        icon: UserIcon,
    },
    {
        id: 'choose-level',
        title: 'Step 3 – Select Level',
        subtitle: 'Choose your programme level to begin your application.',
        icon: FileTextIcon,
    },
    {
        id: 'pay-fee',
        title: 'Step 4 – Pay Application Fee',
        subtitle: 'Complete the application fee where required.',
        icon: CreditCardIcon,
    },
    {
        id: 'complete-application',
        title: 'Step 5 – Complete Application',
        subtitle: 'Submit your full application and track its status.',
        icon: ClipboardListIcon,
    },
];

const returningSteps = [
    {
        id: 'lookup',
        title: 'Step 1 – Find Your Record',
        subtitle: 'Look up your account using National ID or student number.',
        icon: SearchIcon,
    },
    {
        id: 'login',
        title: 'Step 2 – Sign In',
        subtitle: 'Log in with your registered email and password.',
        icon: LogInIcon,
    },
    {
        id: 'choose-level',
        title: 'Step 3 – Select Level',
        subtitle: 'Continue with level and programme selection.',
        icon: FileTextIcon,
    },
    {
        id: 'track',
        title: 'Step 4 – Track Application',
        subtitle: 'Monitor progress and update your application when needed.',
        icon: ClipboardListIcon,
    },
];

const internationalSteps = [
    {
        id: 'verify-passport',
        title: 'Step 1 – Verify Passport',
        subtitle: 'Confirm your passport is not already registered.',
        icon: SearchIcon,
    },
    {
        id: 'create-account',
        title: 'Step 2 – Create an Account',
        subtitle: 'Register with your personal details and secure password.',
        icon: UserIcon,
    },
    {
        id: 'choose-level',
        title: 'Step 3 – Select Level',
        subtitle: 'Choose your programme level to begin your application.',
        icon: FileTextIcon,
    },
    {
        id: 'complete-application',
        title: 'Step 4 – Complete Application',
        subtitle: 'Provide passport details and submit your application.',
        icon: ClipboardListIcon,
    },
];

const visibleSteps = computed(() => {
    if (props.activePath === 'returning') {
        return returningSteps;
    }
    if (props.activePath === 'international') {
        return internationalSteps;
    }
    return newStudentSteps;
});

const highlightedStep = computed(() => {
    if (props.activePath === 'returning') {
        return 'lookup';
    }
    if (props.activePath === 'international') {
        return 'verify-passport';
    }
    return 'verify-identity';
});
</script>

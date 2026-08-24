<script setup lang="ts">
import AuthBackground from '@/components/auth/AuthBackground.vue';
import AppLogoMark from '@/components/core/image/AppLogoMark.vue';
import AppearanceCycleToggle from '@/components/core/util/AppearanceCycleToggle.vue';
import PublicShell from '@/layouts/PublicShell.vue';

withDefaults(
    defineProps<{
        showHeader?: boolean;
    }>(),
    { showHeader: true },
);

const appName = import.meta.env.VITE_APP_NAME || 'Harare Polytechnic';
</script>

<template>
    <PublicShell transparent-background :show-appearance-toggle="false">
        <AuthBackground />
        <div class="relative isolate flex min-h-svh flex-col overflow-y-auto p-4 sm:p-6 md:p-10">
            <header v-if="showHeader" class="relative z-10 mb-6 grid grid-cols-[1fr_auto_1fr] items-center gap-x-2 md:mb-8">
                <div aria-hidden="true" class="md:hidden" />

                <a
                    :href="route('login')"
                    class="col-start-2 flex min-h-11 items-center gap-3 justify-self-center rounded-lg px-2 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 focus-visible:outline-none md:col-start-1 md:justify-self-start"
                >
                    <AppLogoMark size="md" />
                    <span class="text-lg font-bold tracking-tight text-white">{{ appName }}</span>
                </a>

                <div class="col-start-3 flex items-center justify-self-end">
                    <AppearanceCycleToggle variant="on-dark" />
                </div>
            </header>

            <div class="relative z-10 mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-start gap-6 py-4 md:justify-center">
                <slot />
                <div
                    class="px-4 py-2 text-center text-xs leading-relaxed text-balance text-white/90 [&_a]:font-medium [&_a]:text-white [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-white"
                >
                    {{ $t('trans.auth_legal_agreement') }}
                    <a href="#">{{ $t('trans.terms_of_service') }}</a>
                    {{ $t('trans.and') }}
                    <a href="#">{{ $t('trans.privacy_policy') }}</a>.
                </div>
            </div>
        </div>
    </PublicShell>
</template>

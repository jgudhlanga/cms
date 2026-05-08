<script setup lang="ts">
import AppLogo from '@/components/core/image/AppLogo.vue';
</script>
<template>
    <div class="relative isolate flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-transparent p-6 md:p-10">
        <div class="relative z-10 flex w-full max-w-sm flex-col gap-6">
            <a href="#" class="flex items-center gap-2 self-center text-xl font-bold uppercase">
                <div class="mb-5 flex size-25 items-center justify-center rounded-full">
                    <AppLogo classes="border-2 border-white" />
                </div>
            </a>
            <slot />
            <div class="text-muted-foreground hover:[&_a]:text-primary text-center text-xs text-balance [&_a]:underline [&_a]:underline-offset-4">
                <a href="#">{{ $t('trans.terms_of_service') }}</a> {{ $t('trans.and') }} <a href="#">{{ $t('trans.privacy_policy') }}</a
                >.
            </div>
        </div>
    </div>
</template>

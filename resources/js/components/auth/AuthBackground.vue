<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

const AUTH_BACKGROUND_WEBP = '/assets/images/poly.webp';
const AUTH_BACKGROUND_PNG = '/assets/images/poly.png';

withDefaults(
    defineProps<{
        contained?: boolean;
    }>(),
    {
        contained: false,
    },
);
</script>

<template>
    <Head>
        <link rel="preload" as="image" :href="AUTH_BACKGROUND_WEBP" type="image/webp" fetchpriority="high" />
    </Head>

    <div aria-hidden="true" class="auth-background pointer-events-none inset-0 overflow-hidden" :class="contained ? 'absolute z-0' : 'fixed -z-10'">
        <div class="absolute inset-0 bg-slate-950">
            <picture class="block h-full w-full">
                <source :srcset="AUTH_BACKGROUND_WEBP" type="image/webp" />
                <img
                    :src="AUTH_BACKGROUND_PNG"
                    alt=""
                    class="h-full w-full object-cover object-center"
                    fetchpriority="high"
                    loading="eager"
                    decoding="async"
                />
            </picture>
        </div>
        <div
            class="absolute inset-0 bg-[linear-gradient(180deg,rgba(15,23,42,0.78)_0%,rgba(15,23,42,0.62)_40%,rgba(15,23,42,0.82)_100%)] dark:bg-[linear-gradient(180deg,rgba(2,6,23,0.90)_0%,rgba(2,6,23,0.78)_40%,rgba(2,6,23,0.92)_100%)]"
        />
    </div>
</template>

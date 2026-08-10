<script setup lang="ts">
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
    <div aria-hidden="true" class="auth-background pointer-events-none inset-0 overflow-hidden" :class="contained ? 'absolute z-0' : 'fixed -z-10'">
        <div class="absolute inset-0 bg-[url('/assets/images/poly.png')] bg-cover bg-center bg-no-repeat" />
        <div
            class="absolute inset-0 bg-[linear-gradient(180deg,rgba(15,23,42,0.78)_0%,rgba(15,23,42,0.62)_40%,rgba(15,23,42,0.82)_100%)] dark:bg-[linear-gradient(180deg,rgba(2,6,23,0.90)_0%,rgba(2,6,23,0.78)_40%,rgba(2,6,23,0.92)_100%)]"
        />
    </div>
</template>

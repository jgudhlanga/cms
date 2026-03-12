<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Profile" />
    <div>Profile</div>
</template>

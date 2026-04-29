<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail: boolean;
    status?: string;
}>();
</script>

<template>
    <Head :title="$tChoice('trans.profile', 1)" />
    <div>{{ $tChoice('trans.profile', 1) }}</div>
</template>

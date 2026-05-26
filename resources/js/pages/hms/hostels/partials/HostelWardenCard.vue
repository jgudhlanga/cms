<script setup lang="ts">
import { ShieldUser } from '@lucide/vue';

interface Props {
    wardenName: string;
    hostelName: string;
}

defineProps<Props>();
</script>

<template>
    <div class="rounded-2xl bg-gradient-to-br from-indigo-950 to-indigo-900 p-6 text-white">
        <div
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full border-2 border-white/25 bg-white/15"
        >
            <ShieldUser class="h-7 w-7 text-white/90" />
        </div>
        <div class="font-serif text-xl font-bold">{{ wardenName }}</div>
        <div class="mt-1 text-xs text-white/50">
            {{ $t('hms.show_warden_role', { hostel: hostelName }) }}
        </div>
        <div class="my-4 h-px bg-white/10" />
        <p class="text-sm text-white/60">
            {{ $t('hms.show_warden_contact_unavailable') }}
        </p>
    </div>
</template>

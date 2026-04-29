<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useUtils } from '@/composables/core/useUtils';

defineProps<{ src: any; name: any; isNumber?: boolean }>();
const { extractInitials } = useUtils();
</script>

<template>
    <Avatar>
        <AvatarImage :src="src" :alt="$t('trans.ui_image')" />
        <AvatarFallback>{{ extractInitials(name, isNumber) }}</AvatarFallback>
    </Avatar>
</template>

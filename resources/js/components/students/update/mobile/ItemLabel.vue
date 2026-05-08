<script setup lang="ts">
import { Label } from '@/components/ui/label';
interface Props {
    label: string;
}
defineProps<Props>();
</script>

<template>
    <Label class="mb-2 text-xs font-bold uppercase">{{ label }}</Label>
</template>

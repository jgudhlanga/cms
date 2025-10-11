<script setup lang="ts">
import { Label } from '@/components/ui/label';
interface Props {
    label: string;
}
defineProps<Props>();
</script>

<template>
    <Label class="text-xs uppercase font-bold mb-2">{{ label }}</Label>
</template>


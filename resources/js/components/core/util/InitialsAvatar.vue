<script setup lang="ts">
import { useUtils } from "@/composables/core/useUtils";
import Avatar from './Avatar.vue';

const props = withDefaults(defineProps<{label: string, isNumber?: boolean }>(), {
	isNumber: false,
})
const {extractInitials} = useUtils()
</script>

<template>
	<Avatar src="" :name="label" :is-number="isNumber" />
</template>

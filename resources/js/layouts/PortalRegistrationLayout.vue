<script setup lang="ts">
import ErrorDialog from '@/components/core/modal/ErrorDialog.vue';
import PublicShell from '@/layouts/PublicShell.vue';
</script>

<template>
	<PublicShell :show-appearance-toggle="false">
		<slot />
		<ErrorDialog />
	</PublicShell>
</template>

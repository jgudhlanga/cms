<script setup lang="ts">
import PublicShell from '@/layouts/PublicShell.vue';
</script>

<template>
	<PublicShell>
		<slot />
		<ErrorDialog />
	</PublicShell>
</template>

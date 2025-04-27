<script setup lang="ts">
import { IconName } from '@/enums/icons';
import { BaseInputWithIcon } from '@/components/core/form';
</script>

<template>
	<BaseInputWithIcon
		:icon="IconName.search"
		:placeholder="`${$t('trans.search')}`"
		class="rounded-full"
	/>
</template>


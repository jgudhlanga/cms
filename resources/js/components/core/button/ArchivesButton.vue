<script setup lang="ts">
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';

</script>

<template>
	<BaseButton type="button" :variant="ColorVariant.warning_outline">
		<BaseIcon :name="IconName.archive" :color="ColorVariant.warning" />
		<span>{{ $tChoice('trans.archive', 1) }}</span>
	</BaseButton>
</template>


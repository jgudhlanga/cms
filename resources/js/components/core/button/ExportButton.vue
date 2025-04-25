<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';

</script>

<template>
	<BaseButton type="button" :variant="ColorVariant.success_outline">
		<BaseIcon :name="IconName.export" :color="ColorVariant.success" />
		<span>{{ $t('trans.export') }}</span>
	</BaseButton>
</template>

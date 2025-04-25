<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';

</script>

<template>
	<BaseButton type="button" :variant="ColorVariant.fuchsia_outline">
		<BaseIcon :name="IconName.back" :color="ColorVariant.fuchsia" />
		<span>{{ $t('trans.back') }}</span>
	</BaseButton>
</template>

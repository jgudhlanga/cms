<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';
import { trans } from 'laravel-vue-i18n';
import { computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
	defineProps<{
		label?: string;
		variant?: ColorVariant;
	}>(),
	{
		variant: ColorVariant.primary,
	},
);

const attrs = useAttrs();
const passthroughAttrs = computed(() => {
	const { variant: _variant, ...rest } = attrs;

	return rest;
});

const buttonLabel = computed(() => props.label ?? trans('trans.add_new'));
</script>

<template>
	<BaseButton type="button" :variant="props.variant" v-bind="passthroughAttrs">
		<BaseIcon :name="IconName.add" :color="ColorVariant.white" />
		<span>{{ buttonLabel }}</span>
	</BaseButton>
</template>

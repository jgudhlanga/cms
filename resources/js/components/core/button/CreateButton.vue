<script setup lang="ts">
import BaseButton from '@/components/core/button/BaseButton.vue';
import BaseIcon from '@/components/core/icon/BaseIcon.vue';
import { IconName } from '@/enums/icons';
import { ColorVariant } from '@/enums/colors';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
	label?: string;
}>();

const buttonLabel = computed(() => props.label ?? trans('trans.add_new'));
</script>

<template>
	<BaseButton type="button" :variant="ColorVariant.primary" v-bind="$attrs">
		<BaseIcon :name="IconName.add" :color="ColorVariant.white" />
		<span>{{ buttonLabel }}</span>
	</BaseButton>
</template>

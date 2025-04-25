<script setup lang="ts">
import BaseImage from '../image/BaseImage.vue';


defineProps<{ message?: string }>();
</script>
<template>
	<div class="flex flex-col w-full justify-center items-center h-full p-3">
		<BaseImage src="/assets/images/sad.svg" classes="w-[50px] h-[50px]" />
		<div class="text-slate-400 dark:text-slate-200 font-extralight">{{ message ? message : $t('trans.no_data') }}
		</div>
	</div>
</template>

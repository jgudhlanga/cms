<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput input-id="registered_name" :label="$t('trans.registered_name')" v-bind="$attrs" placeholder="" />
</template>

<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput input-id="contact_name" :label="$t('trans.representative_name')" v-bind="$attrs" placeholder="" />
</template>

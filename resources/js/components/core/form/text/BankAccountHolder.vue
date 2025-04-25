<script setup lang="ts">
import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput input-id="bank_account_holder" :label="$t('trans.account_holder_name')" v-bind="$attrs" placeholder="" />
</template>

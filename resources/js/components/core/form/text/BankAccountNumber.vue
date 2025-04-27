<script setup lang="ts">

import BaseInput from './BaseInput.vue';
</script>

<template>
	<BaseInput input-id="bank_account_number" :label="$tChoice('trans.account_number', 1)" v-bind="$attrs" placeholder="" />
</template>


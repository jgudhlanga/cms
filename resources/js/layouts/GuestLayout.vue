<script setup lang="ts">
import AppLogo from '@/components/core/image/AppLogo.vue';

</script>
<template>
	<div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-transparent p-6 md:p-10">
		<div class="flex w-full max-w-sm flex-col gap-6">
			<a href="#" class="flex items-center gap-2 self-center font-bold uppercase text-xl">
				<div
					class="flex size-25 items-center justify-center rounded-full mb-5">
					<AppLogo classes="border-2 border-white" />
				</div>
			</a>
			<slot />
			<div
				class="text-balance text-center text-xs text-muted-foreground [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-primary  ">
				<a href="#">{{ $t('trans.terms_of_service') }}</a> {{ $t('trans.and') }} <a
				href="#">{{ $t('trans.privacy_policy') }}</a>.
			</div>
		</div>
	</div>
</template>

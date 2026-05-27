<script setup lang="ts">
import AppLogoMark from '@/components/core/image/AppLogoMark.vue';
import PublicShell from '@/layouts/PublicShell.vue';
</script>
<template>
	<PublicShell>
		<div class="relative isolate flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-transparent p-6 md:p-10">
			<div class="relative z-10 flex w-full max-w-sm flex-col gap-6">
				<a href="#" class="flex items-center gap-2 self-center text-xl font-bold uppercase">
					<AppLogoMark size="xl" class="mb-5" />
				</a>
				<slot />
				<div
					class="text-center text-xs text-muted-foreground text-balance [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-primary"
				>
					<a href="#">{{ $t('trans.terms_of_service') }}</a> {{ $t('trans.and') }}
					<a href="#">{{ $t('trans.privacy_policy') }}</a>.
				</div>
			</div>
		</div>
	</PublicShell>
</template>

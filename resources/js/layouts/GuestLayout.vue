<script setup lang="ts">
import AuthBackground from '@/components/auth/AuthBackground.vue';
import AppearanceCycleToggle from '@/components/core/util/AppearanceCycleToggle.vue';
import AppLogoMark from '@/components/core/image/AppLogoMark.vue';
import PublicShell from '@/layouts/PublicShell.vue';

withDefaults(
	defineProps<{
		showHeader?: boolean;
	}>(),
	{ showHeader: true },
);

const appName = import.meta.env.VITE_APP_NAME || 'Harare Polytechnic';
</script>

<template>
	<PublicShell transparent-background :show-appearance-toggle="false">
		<AuthBackground />
		<div class="relative isolate flex min-h-svh flex-col p-6 md:p-10">
			<header
				v-if="showHeader"
				class="relative z-10 mb-8 grid grid-cols-[1fr_auto_1fr] items-center gap-x-2"
			>
				<div aria-hidden="true" class="md:hidden" />

				<a
					href="#"
					class="col-start-2 flex items-center gap-3 justify-self-center md:col-start-1 md:justify-self-start"
				>
					<AppLogoMark size="md" />
					<span class="text-lg font-bold tracking-tight text-white">{{ appName }}</span>
				</a>

				<div class="col-start-3 flex items-center justify-self-end">
					<AppearanceCycleToggle variant="on-dark" />
				</div>
			</header>

			<div class="relative z-10 mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center gap-6">
				<slot />
				<div
					class="rounded-full bg-slate-950/50 px-4 py-2 text-center text-xs text-white/90 shadow-sm backdrop-blur-sm text-balance [&_a]:font-medium [&_a]:text-white [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-white"
				>
					<a href="#">{{ $t('trans.terms_of_service') }}</a> {{ $t('trans.and') }}
					<a href="#">{{ $t('trans.privacy_policy') }}</a>.
				</div>
			</div>
		</div>
	</PublicShell>
</template>

import { usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { readAppearance, resolveIsDark } from '@/composables/core/useAppearance';

function readIsDarkFromDom(): boolean {
	if (typeof document === 'undefined') {
		return false;
	}

	return document.documentElement.classList.contains('dark');
}

export function useResolvedTheme() {
	const page = usePage();

	const readIsDark = (): boolean => {
		if (typeof document !== 'undefined') {
			return readIsDarkFromDom();
		}

		const appearance = readAppearance();
		const hint = (page.props.appearance as { systemPrefersDark?: boolean } | undefined)?.systemPrefersDark;

		return resolveIsDark(appearance, hint);
	};

	const isDark = ref(readIsDark());

	onMounted(() => {
		const sync = (): void => {
			isDark.value = readIsDarkFromDom();
		};

		const observer = new MutationObserver(sync);
		observer.observe(document.documentElement, {
			attributes: true,
			attributeFilter: ['class', 'data-resolved-theme'],
		});

		sync();

		onUnmounted(() => observer.disconnect());
	});

	return { isDark };
}

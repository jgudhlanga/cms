import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

export const APPEARANCE_COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

function persistAppearanceCookie(value: Appearance): void {
	document.cookie = `appearance=${encodeURIComponent(value)}; path=/; max-age=${APPEARANCE_COOKIE_MAX_AGE}; SameSite=Lax`;
}

export function updateTheme(value: Appearance) {
	if (value === 'system') {
		const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
		document.documentElement.classList.toggle('dark', systemTheme === 'dark');
	} else {
		document.documentElement.classList.toggle('dark', value === 'dark');
	}
}

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

const handleSystemThemeChange = () => {
	const currentAppearance = localStorage.getItem('appearance') as Appearance | null;
	updateTheme(currentAppearance || 'system');
};

let appearanceListenerAttached = false;

export function initializeTheme() {
	const savedAppearance = localStorage.getItem('appearance') as Appearance | null;
	const value: Appearance = savedAppearance || 'system';
	updateTheme(value);
	persistAppearanceCookie(value);

	if (!appearanceListenerAttached) {
		appearanceListenerAttached = true;
		mediaQuery.addEventListener('change', handleSystemThemeChange);
	}
}

export function useAppearance() {
	const appearance = ref<Appearance>('system');

	onMounted(() => {
		const savedAppearance = localStorage.getItem('appearance') as Appearance | null;
		if (savedAppearance) {
			appearance.value = savedAppearance;
		}
	});

	function updateAppearance(value: Appearance) {
		appearance.value = value;
		localStorage.setItem('appearance', value);
		persistAppearanceCookie(value);
		updateTheme(value);
	}

	return {
		appearance,
		updateAppearance
	};
}

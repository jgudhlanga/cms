import { usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

export type Appearance = 'light' | 'dark' | 'system';

export const APPEARANCE_COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

declare global {
	interface Window {
		__serverPrefersDark?: boolean;
	}
}

const prefersDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');
const webkitPrefersDarkQuery = window.matchMedia('(-webkit-prefers-color-scheme: dark)');

function isValidAppearance(value: string | null | undefined): value is Appearance {
	return value === 'light' || value === 'dark' || value === 'system';
}

function readAppearanceFromLocalStorage(): Appearance | null {
	try {
		const stored = localStorage.getItem('appearance');

		return isValidAppearance(stored) ? stored : null;
	} catch {
		return null;
	}
}

function readAppearanceFromCookie(): Appearance | null {
	try {
		const match = document.cookie.match(/(?:^|;\s*)appearance=([^;]*)/);

		if (!match) {
			return null;
		}

		const value = decodeURIComponent(match[1]);

		return isValidAppearance(value) ? value : null;
	} catch {
		return null;
	}
}

export function readAppearance(): Appearance {
	return readAppearanceFromLocalStorage() ?? readAppearanceFromCookie() ?? 'system';
}

function serverPrefersDark(): boolean {
	return window.__serverPrefersDark === true;
}

/** Detect OS / browser dark preference (with server Client Hint fallback). */
export function prefersColorSchemeDark(): boolean {
	return prefersDarkQuery.matches || webkitPrefersDarkQuery.matches || serverPrefersDark();
}

export function resolveIsDark(appearance: Appearance, systemPrefersDarkHint?: boolean): boolean {
	if (appearance === 'dark') {
		return true;
	}

	if (appearance === 'light') {
		return false;
	}

	if (systemPrefersDarkHint === true) {
		return true;
	}

	return prefersColorSchemeDark();
}

export function applyTheme(appearance: Appearance, systemPrefersDarkHint?: boolean): void {
	const root = document.documentElement;
	const isDark = resolveIsDark(appearance, systemPrefersDarkHint);

	root.classList.remove('light', 'dark');
	root.dataset.appearance = appearance;
	root.dataset.resolvedTheme = isDark ? 'dark' : 'light';

	if (appearance === 'light') {
		root.classList.add('light');
		root.style.colorScheme = 'light';

		return;
	}

	if (isDark) {
		root.classList.add('dark');
	}

	root.style.colorScheme = isDark ? 'dark' : 'light';
}

function persistAppearance(value: Appearance): void {
	document.cookie = `appearance=${encodeURIComponent(value)}; path=/; max-age=${APPEARANCE_COOKIE_MAX_AGE}; SameSite=Lax`;

	try {
		localStorage.setItem('appearance', value);
	} catch {
		// Storage may be unavailable in private browsing
	}
}

/** @deprecated Use applyTheme instead */
export function updateTheme(value: Appearance): void {
	applyTheme(value);
}

const handleSystemThemeChange = (): void => {
	if (readAppearance() !== 'system') {
		return;
	}

	applyTheme('system');
};

let appearanceListenerAttached = false;

export function initializeTheme(): void {
	const appearance = readAppearance();

	applyTheme(appearance, serverPrefersDark());
	persistAppearance(appearance);

	if (!appearanceListenerAttached) {
		appearanceListenerAttached = true;
		prefersDarkQuery.addEventListener('change', handleSystemThemeChange);
		webkitPrefersDarkQuery.addEventListener('change', handleSystemThemeChange);
	}
}

export function useAppearance() {
	const page = usePage();
	const appearance = ref<Appearance>(readAppearance());

	const systemPrefersDarkHint = (): boolean | undefined => {
		const shared = page.props.appearance as { systemPrefersDark?: boolean } | undefined;

		return shared?.systemPrefersDark;
	};

	const syncTheme = (): void => {
		applyTheme(appearance.value, systemPrefersDarkHint());
	};

	onMounted(() => {
		appearance.value = readAppearance();
		syncTheme();
	});

	watch(
		() => page.props.appearance,
		() => {
			if (appearance.value === 'system') {
				syncTheme();
			}
		},
	);

	function updateAppearance(value: Appearance): void {
		appearance.value = value;
		persistAppearance(value);
		applyTheme(value, systemPrefersDarkHint());
	}

	return {
		appearance,
		updateAppearance,
	};
}

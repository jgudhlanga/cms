import '../css/app.css';

import ConfirmDialog from '@/components/core/modal/ConfirmDialog.vue';
import ErrorDialog from '@/components/core/modal/ErrorDialog.vue';
import { initializeTheme } from '@/composables/core/useAppearance';
import { trackPageHistory } from '@/lib/navigationHistory';
import { layoutNameForPage, type PageLayoutName } from '@/lib/pageLayouts';
import { forgetCachedPages } from '@/lib/prefetch';
import { PageModule } from '@/types';
import { createInertiaApp, router } from '@inertiajs/vue3';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { i18nVue } from 'laravel-vue-i18n';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import type { DefineComponent, VNode } from 'vue';
import { createApp, h } from 'vue';
import { createVfm } from 'vue-final-modal';
import 'vue-final-modal/style.css';
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Keep Laravel session previous URL on Inertia pages, not API/XHR endpoints.
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

initializeTheme();

// Form submissions can change any list page or lookup, so prefetched pages and dropdown lists are dropped.
router.on('finish', (event) => {
    if (event.detail.visit.method !== 'get') {
        forgetCachedPages();
    }
});

trackPageHistory();

// Mount without translations after this long rather than leave the page blank if they fail to load.
const TRANSLATIONS_MOUNT_TIMEOUT_MS = 3000;

// Layouts load on demand too: guests never download the app sidebar, and staff never download guest shells.
const layoutLoaders: Record<PageLayoutName, () => Promise<unknown>> = {
    guest: async () => {
        const { default: GuestLayout } = await import('@/layouts/GuestLayout.vue');

        return (render: typeof h, page: VNode) => render(GuestLayout, { showHeader: false }, () => page);
    },
    'portal-registration': async () => (await import('@/layouts/PortalRegistrationLayout.vue')).default,
    plain: async () => (await import('@/layouts/PlainLayout.vue')).default,
    app: async () => (await import('@/layouts/AppLayout.vue')).default,
};

// One promise per layout, so every page shares the same layout instance and persistent layouts keep state.
const loadedLayouts = new Map<PageLayoutName, Promise<unknown>>();

const loadLayout = (name: PageLayoutName): Promise<unknown> => {
    if (!loadedLayouts.has(name)) {
        loadedLayouts.set(name, layoutLoaders[name]());
    }

    return loadedLayouts.get(name) as Promise<unknown>;
};

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);
const vfm = createVfm(); // MODAL PLUGIN
createInertiaApp({
    title: (title) => `${title ? title.toUpperCase() + ' - ' : ''} ${appName}`,
    // Each page is its own chunk, fetched on first visit instead of shipping every page in the entry bundle.
    resolve: async (name) => {
        const [page, layout] = await Promise.all([
            resolvePageComponent<PageModule>(`./pages/${name}.vue`, import.meta.glob<PageModule>('./pages/**/*.vue')),
            loadLayout(layoutNameForPage(name)),
        ]);
        page.default.layout = layout;

        // Inertia accepts the module and reads its default export; its types only describe the component.
        return page as unknown as DefineComponent;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        let mounted = false;
        const mount = () => {
            if (!mounted) {
                mounted = true;
                app.mount(el);
            }
        };

        app.use(plugin)
            .use(ZiggyVue)
            .use(pinia)
            .use(i18nVue, {
                resolve: async (lang: string | undefined) => {
                    const defaultLang = 'en'; // Default language
                    lang = lang || defaultLang; // Fallback if lang is undefined
                    try {
                        const allLang = import.meta.glob('../../lang/*.json');
                        const langLoader = allLang[`../../lang/${lang}.json`] || allLang[`../../lang/${defaultLang}.json`];
                        if (langLoader) {
                            return await langLoader();
                        }
                    } catch (error) {
                        console.error(`Failed to load translations for "${lang}".`, error);
                        return {};
                    }
                },
                // Mount once translations are in, so raw translation keys never flash on screen.
                onLoad: mount,
            })
            .use(vfm)
            .use(Vue3Toastify);
        // ✅ Register ConfirmDialog globally
        app.component('ConfirmDialog', ConfirmDialog);
        app.component('errorDialog', ErrorDialog);
        window.setTimeout(mount, TRANSLATIONS_MOUNT_TIMEOUT_MS);
    },
    progress: {
        color: '#30A8FF',
        // Fast (cached or prefetched) visits finish before the bar would show, so it no longer flickers.
        delay: 250,
        showSpinner: true,
    },
});

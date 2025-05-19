import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { i18nVue } from 'laravel-vue-i18n';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import Vue3Toastify from 'vue3-toastify';
import { createVfm } from 'vue-final-modal';
import 'vue3-toastify/dist/index.css';
import 'vue-final-modal/style.css';
import { initializeTheme } from '@/composables/core/useAppearance';
import GuestLayout from '@/layouts/GuestLayout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PlainLayout from '@/layouts/PlainLayout.vue';
import { PageModule } from '@/types';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);
const vfm = createVfm(); // MODAL PLUGIN
createInertiaApp({
    title: (title) => `${title ? title + ' - ' : ''} ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob<PageModule>('./pages/**/*.vue', { eager: true });
        const page = pages[`./pages/${name}.vue`];
        if (name.startsWith('auth/')) {
            page.default.layout = GuestLayout;
        } else if (name.startsWith('site/')) {
            page.default.layout = PlainLayout;
        } else {
            page.default.layout = AppLayout;
        }
        return page as { default: DefineComponent };
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
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
                }
            })
            .use(vfm)
            .use(Vue3Toastify);
        app.mount(el);
    },
    progress: {
        color: '#30A8FF',
        showSpinner: true
    }
}).then();

// This will set light / dark mode on a page load...
initializeTheme();

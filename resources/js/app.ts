import '../css/app.css';

import ConfirmDialog from '@/components/core/modal/ConfirmDialog.vue';
import ErrorDialog from '@/components/core/modal/ErrorDialog.vue';
import { initializeTheme } from '@/composables/core/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import PortalRegistrationLayout from '@/layouts/PortalRegistrationLayout.vue';
import PlainLayout from '@/layouts/PlainLayout.vue';
import { PageModule } from '@/types';
import { createInertiaApp } from '@inertiajs/vue3';
import { MotionPlugin } from '@vueuse/motion';
import { i18nVue } from 'laravel-vue-i18n';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import 'temporal-polyfill/global';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { createVfm } from 'vue-final-modal';
import 'vue-final-modal/style.css';
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

initializeTheme();

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);
const vfm = createVfm(); // MODAL PLUGIN
createInertiaApp({
    title: (title) => `${title ? title.toUpperCase() + ' - ' : ''} ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob<PageModule>('./pages/**/*.vue', { eager: true });
        const page = pages[`./pages/${name}.vue`];
        if (name.startsWith('auth/')) {
            page.default.layout =
                name === 'auth/Login'
                    ? (h, page) => h(GuestLayout, { showHeader: false }, () => page)
                    : GuestLayout;
        } else if (name === 'portal/guest/RegistrationUserForm') {
            page.default.layout = PortalRegistrationLayout;
        } else if (
            name.startsWith('site/') ||
            name.startsWith('portal/guest') ||
            name.startsWith('portal/application') ||
            name.startsWith('integrations')
        ) {
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
                },
            })
            .use(vfm)
            .use(MotionPlugin)
            .use(Vue3Toastify);
        // ✅ Register ConfirmDialog globally
        app.component('ConfirmDialog', ConfirmDialog);
        app.component('errorDialog', ErrorDialog);
        app.mount(el);
    },
    progress: {
        color: '#30A8FF',
        showSpinner: true,
    },
});

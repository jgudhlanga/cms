import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import i18n from 'laravel-vue-i18n/vite';
import { resolve } from 'node:path';
import path from 'path';
import { defineConfig } from 'vite';
import Components from "unplugin-vue-components/vite"

export default defineConfig({
    plugins: [
        Components({
            dirs: ['resources/js/components', 'resources/js/layouts'],
            deep: true,
            dts: 'resources/js/types/components.d.ts',
        }),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
            detectTls: false,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        i18n(),
    ],
    optimizeDeps: {
        include: ['@wangeditor/editor-for-vue'],
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
});

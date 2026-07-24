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
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('@wangeditor') || id.includes('wangeditor')) {
                        return 'editor';
                    }

                    if (id.includes('chart.js') || id.includes('vue-chart') || id.includes('apexcharts')) {
                        return 'charts';
                    }

                    if (id.includes('@fullcalendar') || id.includes('fullcalendar')) {
                        return 'calendar';
                    }

                    if (id.includes('exceljs') || id.includes('xlsx') || id.includes('file-saver')) {
                        return 'spreadsheet';
                    }

                    if (id.includes('@vue') || id.includes('vue/') || id.includes('vue-router') || id.includes('@inertiajs')) {
                        return 'vue-vendor';
                    }
                },
            },
        },
    },
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

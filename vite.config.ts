import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
        template: {
            transformAssetUrls: {
                base: null,
                includeAbsolute: false,
            },
        },
    }),
],
build: {
    rollupOptions: {
        output: {
            manualChunks: {
                fullcalendar: ['@fullcalendar/core', '@fullcalendar/vue3'],
                'fullcalendar-plugins': ['@fullcalendar/daygrid', '@fullcalendar/timegrid', '@fullcalendar/interaction'],
            },
        },
    },
},
server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        },
        cors: true,
    },
});

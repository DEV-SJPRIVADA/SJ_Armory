import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));

/** Build para subir al hosting: sale en build_hosting/build (no toca public/build local). Variables: build_hosting/.env.production */
export default defineConfig({
    envDir: resolve(__dirname, 'build_hosting'),
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/map.js',
                'resources/js/location-picker.js',
            ],
            refresh: false,
            publicDirectory: 'build_hosting',
            hotFile: 'build_hosting/hot',
        }),
    ],
});

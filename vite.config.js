import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/scss/preview.scss', 'resources/js/app.js', 'resources/js/admin/cropper.js'],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            // Bootstrap 5.3 utilise encore @import : on masque ces avertissements de dépréciation.
            scss: { quietDeps: true, silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'] },
        },
    },
    server: {
        host: '0.0.0.0',
        hmr: { host: 'localhost' },
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**', '**/legacy/**'],
        },
    },
});

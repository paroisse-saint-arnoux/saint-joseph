import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Serveur de développement derrière Traefik (docker compose --profile front up node) :
// VITE_DEV_ORIGIN = https://saint-joseph-vite.dev.saint-arnoux.fr. Sinon, http://localhost:5173.
const devOrigin = process.env.VITE_DEV_ORIGIN ? new URL(process.env.VITE_DEV_ORIGIN) : null;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/scss/preview.scss', 'resources/js/app.js', 'resources/js/admin/cropper.js', 'resources/scss/consultation.scss', 'resources/js/consultation.js', 'resources/scss/croix.scss', 'resources/js/croix.js', 'resources/scss/huile.scss', 'resources/js/huile.js'],
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
        ...(devOrigin
            ? {
                origin: devOrigin.origin,
                hmr: { host: devOrigin.hostname, protocol: devOrigin.protocol === 'https:' ? 'wss' : 'ws', clientPort: devOrigin.protocol === 'https:' ? 443 : 80 },
                cors: { origin: /^https?:\/\/([a-z0-9-]+\.)*dev\.saint-arnoux\.fr$/ },
            }
            : { hmr: { host: 'localhost' } }),
        allowedHosts: ['.dev.saint-arnoux.fr', 'localhost'],
        watch: {
            ignored: ['**/storage/framework/views/**', '**/vendor/**', '**/legacy/**'],
        },
    },
});

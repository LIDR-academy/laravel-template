import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        // Bind to all interfaces so the dev server is reachable from outside the container.
        host: '0.0.0.0',
        port: 5173,
        // HMR client connects to the host-mapped port.
        hmr: {
            host: 'localhost',
        },
        watch: {
            // Polling is needed for file changes to be detected inside Docker on some hosts.
            usePolling: true,
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

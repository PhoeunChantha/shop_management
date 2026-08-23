import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            // Admin Settings writes .env-backed values (e.g. OAuth keys). The
            // bundle reads no VITE_* variables, so don't let a .env write restart
            // the dev server and force-reload the page mid-session.
            ignored: ['**/.env', '**/.env.*'],
        },
    },
});

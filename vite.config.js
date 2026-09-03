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
        // Pin to IPv4 loopback — on Windows, Vite's default "localhost" host
        // often resolves to the IPv6 literal [::1], which Chrome's CSP
        // parser rejects as an invalid source no matter how it's written.
        host: '127.0.0.1',
    },
});

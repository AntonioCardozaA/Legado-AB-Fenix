import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/auth-tailwind.css',
                'resources/css/dashboard-lavadora.css',
                'resources/css/dashboard-pasteurizadora.css',
                'resources/css/lavadora-menu.css',
                'resources/js/app.js',
                'resources/js/dashboard-lavadora-panels.js',
                'resources/js/dashboard-pasteurizadora.js',
            ],
            refresh: true,
        }),
    ],
});

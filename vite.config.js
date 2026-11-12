import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Sitio actual (Blade + Alpine). Se deja intacto.
                'resources/css/app.css',
                'resources/js/app.js',
                // Sitio público y Zona de Clientes con osole/ui (rediseño 2026).
                'resources/css/sitio.css',
                'resources/js/sitio.js',
                // Asesor IA (Inertia + React).
                'resources/css/assistant.css',
                'resources/js/app.tsx',
                // Widget del asesor embebido en la Zona de Clientes (Blade).
                'resources/js/advisor.tsx',
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
});

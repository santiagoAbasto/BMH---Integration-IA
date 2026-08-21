import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';

const appName = 'BMH · Asesor Técnico';

type PageModule = { default: ComponentType<Record<string, unknown>> };

void createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),

    resolve: (name) => {
        // Eager: son pocas páginas y evita un chunk extra en el primer render
        // del chat, que es la pantalla crítica.
        const pages = import.meta.glob<PageModule>('./pages/**/*.tsx', { eager: true });

        const page = pages[`./pages/${name}.tsx`];

        if (page === undefined) {
            throw new Error(`No existe la página Inertia "${name}".`);
        }

        return page;
    },

    setup({ el, App, props }) {
        if (el === null) {
            throw new Error('Falta el contenedor de Inertia en la vista raíz.');
        }

        createRoot(el).render(<App {...props} />);
    },

    progress: {
        color: '#0098DA',
        showSpinner: false,
    },
});

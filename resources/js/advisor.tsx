import { createRoot } from 'react-dom/client';
import { AdvisorWidget } from '@/features/assistant/components/AdvisorWidget';
import type { AssistantPageProps } from '@/features/assistant/types';
// `?inline` devuelve el CSS compilado como string en vez de inyectarlo en el
// <head>. Es lo que permite meterlo dentro del shadow root.
import styles from '../css/advisor-widget.css?inline';

/**
 * Punto de entrada del asesor dentro de la Zona de Clientes (Blade + Bootstrap).
 *
 * Se monta en un SHADOW ROOT a propósito. El sitio actual corre sobre Bootstrap
 * 5, que comparte nombres de clase con Tailwind (`.table`, `.border`,
 * `.shadow`, `.rounded`, `.visible`, `.fixed`). Cargar Tailwind en el documento
 * rompería el catálogo, el carrito y el header. Dentro del shadow, los estilos
 * de los dos lados quedan aislados en ambas direcciones.
 */

type Bootstrapped = {
    customer: AssistantPageProps['customer'];
    recentPurchases: AssistantPageProps['recentPurchases'];
    settings: AssistantPageProps['settings'];
};

function readConfig(host: HTMLElement): Bootstrapped | null {
    const raw = host.dataset.advisor;

    if (raw === undefined || raw === '') {
        return null;
    }

    try {
        return JSON.parse(raw) as Bootstrapped;
    } catch {
        console.warn('[BMH] No se pudo leer la configuración del asesor.');

        return null;
    }
}

function mount(): void {
    const host = document.getElementById('bmh-advisor');

    if (host === null || host.dataset.mounted === '1') {
        return;
    }

    const config = readConfig(host);

    if (config === null) {
        return;
    }

    host.dataset.mounted = '1';

    const shadow = host.attachShadow({ mode: 'open' });

    const sheet = document.createElement('style');
    sheet.textContent = styles;
    shadow.appendChild(sheet);

    // Inter desde el documento principal: @font-face no cruza el shadow
    // boundary, así que la fuente se declara afuera y acá sólo se referencia.
    if (document.getElementById('bmh-advisor-font') === null) {
        const link = document.createElement('link');
        link.id = 'bmh-advisor-font';
        link.rel = 'stylesheet';
        link.href = 'https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:400,500';
        document.head.appendChild(link);
    }

    const container = document.createElement('div');
    shadow.appendChild(container);

    createRoot(container).render(
        <AdvisorWidget
            customer={config.customer}
            recentPurchases={config.recentPurchases}
            settings={config.settings}
        />,
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}

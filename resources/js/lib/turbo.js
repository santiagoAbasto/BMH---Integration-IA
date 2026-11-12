// Navegación sin recarga (Hotwire Turbo), como en Conelec: el sitio se comporta como una SPA.
import * as Turbo from '@hotwired/turbo';

// Formularios en modo opt-in: se envían como siempre (o por fetch), nunca por Turbo sin pedirlo.
Turbo.config.forms.mode = 'optin';
Turbo.config.drive.progressBarDelay = 150;

export { Turbo };

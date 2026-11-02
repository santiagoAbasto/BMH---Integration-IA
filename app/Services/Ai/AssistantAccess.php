<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Beta cerrada del asesor: quién puede verlo y usarlo (config bmh.assistant_access).
 *
 * Una sola regla para todo: el middleware de las rutas (EnsureAssistantAccess)
 * y lo que se muestra en el sitio (el ítem del menú y el widget, ver
 * AdvisorBootstrap::shouldRender). Así un cliente que no está en la beta no ve
 * ningún acceso al asesor, en vez de verlo y recibir un 404 al tocarlo.
 */
final class AssistantAccess
{
    public static function permite(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (config('bmh.assistant_access.mode') === 'all') {
            return true;
        }

        $permitidos = array_map('intval', (array) config('bmh.assistant_access.customer_ids', []));

        return in_array((int) $user->getAuthIdentifier(), $permitidos, true);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Ai\AiDemoAccount;
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

        $id = (int) $user->getAuthIdentifier();
        $permitidos = array_map('intval', (array) config('bmh.assistant_access.customer_ids', []));

        // La lista del .env sigue valiendo; las cuentas demo se administran
        // desde el panel (Aprendizaje de la IA) sin tocar el servidor.
        return in_array($id, $permitidos, true) || AiDemoAccount::isActive($id);
    }
}

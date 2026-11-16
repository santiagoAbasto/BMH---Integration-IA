<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

/**
 * Cliente habilitado para probar el asesor y evaluar sus respuestas.
 *
 * Sus 👍/👎 son de confianza: una corrección con código verificado contra el
 * catálogo se aprende sin esperar la revisión de un administrador.
 */
final class AiDemoAccount extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_demo_accounts';

    protected $fillable = ['customer_id', 'label', 'active', 'created_by_admin_id'];

    protected $casts = [
        'customer_id' => 'integer',
        'active' => 'boolean',
        'created_by_admin_id' => 'integer',
    ];

    /** ¿Es esta cuenta una cuenta demo activa? Falla cerrado si la tabla falta. */
    public static function isActive(int $customerId): bool
    {
        try {
            return self::query()->where('customer_id', $customerId)->where('active', true)->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}

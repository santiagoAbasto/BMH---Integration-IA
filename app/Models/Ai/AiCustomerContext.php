<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

/**
 * Un hecho acumulado de la conversación.
 *
 * `state` distingue `confirmed` (el cliente lo dijo o la base lo confirma) de
 * `inferred` (lo dedujo un modelo). Una inferencia nunca se promueve a hecho
 * por su cuenta.
 */
final class AiCustomerContext extends Model
{
    public const STATE_CONFIRMED = 'confirmed';
    public const STATE_INFERRED  = 'inferred';
    public const STATE_UNKNOWN   = 'unknown';

    protected $connection = 'mysql_ai';
    protected $table = 'ai_customer_context';

    protected $fillable = [
        'conversation_id', 'fact_key', 'fact_value', 'state', 'source', 'confidence',
    ];

    protected $casts = ['confidence' => 'float'];
}

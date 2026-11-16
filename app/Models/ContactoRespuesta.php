<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una respuesta escrita desde Contacto › Mensajes. Queda en la conversación aunque el email no salga: `enviado_at`
 * dice que salió; `fallido_at`, que falló (y se puede volver a enviar).
 */
final class ContactoRespuesta extends Model
{
    protected $table = 'contacto_respuestas';

    protected $fillable = ['admin_id', 'autor', 'asunto', 'cuerpo'];

    protected $casts = ['enviado_at' => 'datetime', 'fallido_at' => 'datetime'];

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(ContactoMensaje::class, 'contacto_mensaje_id');
    }

    public function enviada(): bool
    {
        return $this->enviado_at !== null;
    }
}

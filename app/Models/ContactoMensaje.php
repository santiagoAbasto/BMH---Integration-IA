<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Consulta que llegó por el formulario de /contacto (Contacto › Mensajes en el admin). */
final class ContactoMensaje extends Model
{
    protected $table = 'contacto_mensajes';

    /** Motivos del select del formulario (frontend/contacto). */
    public const MOTIVOS = ['Consulta comercial', 'Consulta técnica', 'Precios y disponibilidad', 'Quiero ser cliente', 'Otro'];

    protected $fillable = ['nombre', 'email', 'telefono', 'empresa', 'motivo', 'mensaje', 'ip', 'user_agent'];

    protected $casts = [
        'leido_at' => 'datetime',
        'aviso_enviado_at' => 'datetime',
    ];

    public function scopeNoLeidos(Builder $query): Builder
    {
        return $query->whereNull('leido_at');
    }

    /** Busca en nombre, email, teléfono, empresa, motivo y texto. */
    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return $query;
        }

        $like = '%'.addcslashes($texto, '%_\\').'%';

        return $query->where(function (Builder $q) use ($like): void {
            foreach (['nombre', 'email', 'telefono', 'empresa', 'motivo', 'mensaje'] as $columna) {
                $q->orWhere($columna, 'like', $like);
            }
        });
    }

    public function leido(): bool
    {
        return $this->leido_at !== null;
    }

    public function marcarLeido(): void
    {
        if ($this->leido_at === null) {
            $this->forceFill(['leido_at' => now()])->save();
        }
    }

    /** Las respuestas escritas desde el panel, de la más vieja a la más nueva. */
    public function respuestas(): HasMany
    {
        return $this->hasMany(ContactoRespuesta::class)->oldest()->oldest('id');
    }

    /** Nombre de pila para saludar («Hola Juan,»). */
    public function nombreDePila(): string
    {
        return trim((string) strtok(trim((string) $this->nombre), ' ')) ?: trim((string) $this->nombre);
    }

    /** Asunto para responder desde el cliente de correo. */
    public function asuntoRespuesta(): string
    {
        return 'Re: tu consulta a BMH'.($this->motivo ? ' ('.$this->motivo.')' : '');
    }

    /** mailto: con el asunto y la consulta citada, para el botón «Responder». */
    public function mailtoRespuesta(): string
    {
        $cita = collect(preg_split('/\R/u', (string) $this->mensaje))->map(fn (string $linea) => '> '.$linea)->implode("\n");
        $cuerpo = "Hola {$this->nombre},\n\n\n\n—\nEl ".$this->created_at?->format('d/m/Y H:i')." escribiste:\n".$cita;

        return 'mailto:'.$this->email.'?'.http_build_query(['subject' => $this->asuntoRespuesta(), 'body' => $cuerpo], '', '&', PHP_QUERY_RFC3986);
    }

    /** Cantidad de mensajes sin leer, o 0 si la tabla todavía no existe (migración sin correr). */
    public static function sinLeer(): int
    {
        try {
            return self::query()->noLeidos()->count();
        } catch (\Illuminate\Database\QueryException) {
            return 0;
        }
    }
}

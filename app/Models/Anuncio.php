<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * El anuncio que aparece una vez, en una ventana, cuando un cliente ingresa a la Zona de Clientes.
 *
 * Es una imagen (public/imagenes/{imagen}) que se carga desde el panel. `contenido` es el texto enriquecido de antes:
 * ya no se usa, se conserva por las dudas (migración 2026_11_17_000008).
 */
class Anuncio extends Model
{
    use HasFactory;

    protected $table = 'anuncios';

    protected $casts = [
        'mostrar' => 'boolean',
        'ancho' => 'integer',
        'alto' => 'integer',
    ];

    public static function actual(): self
    {
        return self::query()->find(1) ?? self::query()->forceCreate(['id' => 1, 'mostrar' => false]);
    }

    public function url(): ?string
    {
        return filled($this->imagen) ? asset('imagenes/'.$this->imagen) : null;
    }

    /** Lo ven los clientes: está activado y tiene imagen. */
    public function visible(): bool
    {
        return $this->mostrar && filled($this->imagen);
    }

    /** Ancho / alto, para reservar el lugar antes de que cargue la imagen. 7:5 si no se conoce. */
    public function proporcion(): float
    {
        return $this->ancho && $this->alto ? round($this->ancho / $this->alto, 4) : 1.4;
    }

    public function textoAlternativo(): string
    {
        return filled($this->texto_alternativo) ? $this->texto_alternativo : 'Anuncio de BMH';
    }
}

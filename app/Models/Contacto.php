<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Datos de contacto del sitio. Una sola fila (id 1) que leen la barra de
 * arriba del header, el footer y la página de Contacto.
 */
class Contacto extends Model
{
    use HasFactory;

    protected $table = 'contacto';

    /** Las redes que muestra la barra superior, en su orden en pantalla. */
    public const REDES_BARRA = [
        'tiktok' => 'TikTok',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
    ];

    /** La fila vigente, o una vacía si todavía no se cargó ninguna. */
    public static function actual(): self
    {
        return static::query()->find(1) ?? new static();
    }

    /**
     * Link para llamar: sólo dígitos y el + inicial. «(011) 4482-2609» pasa a
     * «tel:01144822609». Null si no hay teléfono o no tiene números.
     */
    public function telHref(): ?string
    {
        $numero = preg_replace('/(?!^\+)[^\d]/', '', trim((string) $this->tel));

        return $numero !== '' && $numero !== '+' ? 'tel:'.$numero : null;
    }

    /**
     * Redes con link cargado, en el orden de la barra. Una red vacía no se
     * muestra: antes aparecía igual, con un link que no llevaba a ningún lado.
     *
     * @return array<string, string> red => url
     */
    public function redesBarra(): array
    {
        $redes = [];

        foreach (array_keys(self::REDES_BARRA) as $red) {
            if (filled($this->{$red})) {
                $redes[$red] = $this->{$red};
            }
        }

        return $redes;
    }
}

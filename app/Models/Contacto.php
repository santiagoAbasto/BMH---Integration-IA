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

    /** Asunto del aviso de cada consulta cuando no se cargó uno propio. */
    public const ASUNTO_AVISO = 'Nueva consulta desde la web';

    protected $casts = [
        'acuse_recibo' => 'boolean',
    ];

    /**
     * Separa una lista de emails escrita a mano (comas, punto y coma, espacios o saltos de línea),
     * sin repetidos y en minúsculas. No valida: eso lo hace quien la usa.
     *
     * @return list<string>
     */
    public static function separarEmails(?string $texto): array
    {
        $emails = preg_split('/[\s,;]+/u', mb_strtolower(trim((string) $texto)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($emails));
    }

    /**
     * A quién le llegan las consultas de /contacto: los emails de «¿A qué mail querés que lleguen los mensajes?»
     * o, si no se cargó ninguno, el email de contacto del sitio.
     *
     * @return list<string>
     */
    public function destinatariosAviso(): array
    {
        $validos = fn (array $emails) => array_values(array_filter($emails, fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false));

        return $validos(self::separarEmails($this->emails_aviso)) ?: $validos(self::separarEmails($this->mail));
    }

    public function asuntoAviso(): string
    {
        return filled($this->asunto_aviso) ? trim($this->asunto_aviso) : self::ASUNTO_AVISO;
    }

    /** Si se le manda el acuse de recibo a quien escribió (por defecto, sí). */
    public function mandaAcuse(): bool
    {
        return $this->acuse_recibo ?? true;
    }

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

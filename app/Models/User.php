<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Pedido;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Cache por request de los márgenes por categoría (ver
     * margenReventaParaCategoria). No es una columna de BD.
     */
    protected $margenCache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'rol',
        'password',
        'dni',
        'direccion',
        'celular',
        'localidad',
        'provincia',
        'cp',
        'reventa',
        'transporte',
        'username',
        'habilitado'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function pago_pendiente(){
        $pedidos_pendientes = Pedido::where('cliente_id', $this->id)->where('estado', 'Esperando pago')->where('estado_orden', '!=', 'Cancelado')->get();
        $pendiente = 0;
        foreach($pedidos_pendientes as $pedido){
            // Eliminar los puntos que actúan como separadores de miles
            $total = str_replace('.', '', $pedido->total_pedido);

            // Reemplazar la coma por un punto para convertir a float
            $total = str_replace(',', '.', $total);

            // Convertir la cadena a float
            $numero = (float) $total;

            $pendiente += $numero;
        }
        return number_format($pendiente, 2, ',' , '.');
    }

    /**
     * Márgenes de reventa específicos por categoría.
     */
    public function margenesReventa()
    {
        return $this->hasMany(MargenReventa::class, 'user_id');
    }

    /**
     * Margen de reventa efectivo para una categoría:
     * el específico de la categoría tiene prioridad sobre el general
     * (users.reventa). Se cachea por request para no consultar en cada
     * producto de un listado.
     */
    public function margenReventaParaCategoria($categoriaId): float
    {
        if ($this->margenCache === null) {
            $this->margenCache = $this->margenesReventa()->get()->keyBy('categoria_id');
        }

        if (isset($this->margenCache[$categoriaId])) {
            return (float) $this->margenCache[$categoriaId]->porcentaje;
        }

        return (float) $this->reventa;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aplicacion extends Model
{
    use HasFactory;

    protected $table = 'aplicaciones';

    protected $fillable = ['producto_id', 'nombre', 'valor', 'orden'];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}

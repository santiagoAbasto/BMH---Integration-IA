<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParteRelacionada extends Model
{
    protected $fillable = [
        'producto_id',
        'parte_id',
        'orden',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function parte(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'parte_id');
    }
}

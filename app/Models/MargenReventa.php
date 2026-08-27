<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MargenReventa extends Model
{
    use HasFactory;

    protected $table = 'margenes_reventa';

    protected $fillable = ['user_id', 'categoria_id', 'porcentaje'];

    protected $casts = [
        'porcentaje' => 'float',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}

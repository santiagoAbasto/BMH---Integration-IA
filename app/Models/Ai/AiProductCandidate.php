<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

final class AiProductCandidate extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_product_candidates';

    protected $fillable = [
        'message_id', 'product_id', 'product_code', 'confidence', 'confidence_band', 'signals', 'position',
    ];

    protected $casts = [
        'signals'    => 'array',
        'confidence' => 'float',
        'product_id' => 'integer',
        'position'   => 'integer',
    ];
}

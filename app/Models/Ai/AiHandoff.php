<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

final class AiHandoff extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_handoffs';

    protected $fillable = [
        'conversation_id', 'customer_id', 'seller_id', 'reason', 'summary', 'context', 'status',
    ];

    protected $casts = ['context' => 'array'];
}

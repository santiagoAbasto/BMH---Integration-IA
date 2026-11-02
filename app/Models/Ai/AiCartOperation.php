<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

final class AiCartOperation extends Model
{
    protected $connection = 'mysql_ai';

    protected $table = 'ai_cart_operations';

    protected $fillable = [
        'conversation_id', 'message_id', 'feedback_id', 'customer_id',
        'product_id', 'quantity', 'status', 'result',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'message_id' => 'integer',
        'feedback_id' => 'integer',
        'customer_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'result' => 'array',
    ];
}

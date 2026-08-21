<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;

final class AiFeedback extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_feedback';

    protected $fillable = [
        'conversation_id', 'message_id', 'customer_id', 'product_id', 'was_correct', 'comment',
    ];

    protected $casts = ['was_correct' => 'boolean'];
}

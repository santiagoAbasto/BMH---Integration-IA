<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiFeedback extends Model
{
    protected $connection = 'mysql_ai';
    protected $table = 'ai_feedback';

    protected $fillable = [
        'conversation_id', 'message_id', 'customer_id', 'product_id', 'was_correct', 'comment',
        'expected_product_id', 'expected_code', 'reason', 'review_status',
        'reviewed_by_admin_id', 'reviewed_at', 'admin_note',
    ];

    protected $casts = [
        'was_correct' => 'boolean',
        'expected_product_id' => 'integer',
        'reviewed_by_admin_id' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiMessageRecord::class, 'message_id');
    }
}

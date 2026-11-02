<?php

declare(strict_types=1);

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiTestReview extends Model
{
    protected $connection = 'mysql_ai';

    protected $table = 'ai_test_reviews';

    protected $fillable = [
        'conversation_id', 'message_id', 'admin_id', 'family', 'rating',
        'expected_action', 'expected_question_id', 'expected_product_id', 'expected_code',
        'surface', 'severity', 'issue_tags', 'comment', 'question_bank_version',
        'prompt_version', 'provider', 'model',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'message_id' => 'integer',
        'admin_id' => 'integer',
        'expected_product_id' => 'integer',
        'issue_tags' => 'array',
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
